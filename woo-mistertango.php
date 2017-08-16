<?php
/**
 * Plugin Name: Mistertango for WooCommerce
 * Plugin URI: https://mistertango.com
 * Description: Accept credit cards, debit cards, online bank payments and Bitcoins on your WooCommerce store.
 * Version: 3.1.6
 * Author: NovaTemple
 * Author URI: https://novatemple.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * Text Domain: woo-mistertango
 * Domain Path: /languages
 *
 * @package WC_Plugin_Mistertango
 * @author NovaTemple
 *
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main constants.
 *
 * @since 3.0.0
 */
define( 'WC_MISTERTANGO_NAME', 'Mistertango' );
define( 'WC_MISTERTANGO_VERSION', '3.1.6' );

define( 'WC_MISTERTANGO_MIN_PHP', '5.3.0' );
define( 'WC_MISTERTANGO_MIN_WP', '3.5.0' );
define( 'WC_MISTERTANGO_MIN_WC', '2.0.0' );

define( 'WC_MISTERTANGO_URL_WEBSITE', 'https://mistertango.com' );
define( 'WC_MISTERTANGO_URL_WEBSITE_LT', 'https://mistertango.lt' );
define( 'WC_MISTERTANGO_URL_API_JS', 'https://payment.mistertango.com/resources/scripts/mt.collect.js' );
define( 'WC_MISTERTANGO_URL_PLUGIN_SUPPORT', 'https://wordpress.org/support/plugin/woo-mistertango' );
define( 'WC_MISTERTANGO_URL_CLIENT_SUPPORT', 'https://uabmistertango.freshdesk.com' );
define( 'WC_MISTERTANGO_URL_CLIENT_SUPPORT_LT', 'https://mistertango.freshdesk.com' );

define( 'WC_MISTERTANGO_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'WC_MISTERTANGO_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );

/**
 * WC_Plugin_Mistertango class.
 *
 * @class WC_Plugin_Mistertango
 *
 * @since 3.0.0
 */
if ( ! class_exists( 'WC_Plugin_Mistertango' ) ) {
	class WC_Plugin_Mistertango {
		/**
		 * Singleton The reference the *Singleton* instance of this class.
		 *
		 * @since 3.0.0
		 */
		private static $instance;

		/**
		 * Reference to logging class.
		 *
		 * @since 3.0.0
		 */
		private static $log;

		/**
		 * Returns the *Singleton* instance of this class.
		 *
		 * @since 3.0.0
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Private clone method to prevent cloning of the instance of the
		 * *Singleton* instance.
		 *
		 * @since 3.0.0
		 */
		private function __clone() {}

		/**
		 * Private unserialize method to prevent unserializing of the *Singleton*
		 * instance.
		 *
		 * @since 3.0.0
		 */
		private function __wakeup() {}

		/**
		 * Notices array.
		 *
		 * @since 3.0.0
		 */
		public $notices = array();

		/**
		 * Protected constructor to prevent creating a new instance of the
		 * *Singleton* via the `new` operator from outside of this class.
		 *
		 * @since 3.0.0
		 */
		protected function __construct() {
			add_action( 'admin_init', array( $this, 'check_environment' ) );
			add_action( 'admin_notices', array( $this, 'admin_notices' ), 15 );
			add_action( 'plugins_loaded', array( $this, 'init' ) );
		}

		/**
		 * Just log it.
		 *
		 * @since 3.0.0
		 */
		public static function log( $message ) {
			if ( empty( self::$log ) ) {
				self::$log = new WC_Logger();
			}
			self::$log->add( 'mistertango', $message );
		}

		/**
		 * Init the plugin and the gateway after plugins_loaded.
		 *
		 * @since 3.0.0
		 */
		public function init() {
			// Stop the plugin if we're in an incompatible environment.
			if ( self::get_environment_warning() ) {
				return;
			}

			// Init the gateway itself.
			$this->init_gateway();

			// Helper links on Plugins page.
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ), 10, 4 );
			add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 4 );
		}

		/**
		 * The backup sanity check, in case the plugin is activated in a weird way,
		 * or the environment changes after activation or it is incompatible.
		 *
		 * @since 3.0.0
		 */
		public function check_environment() {
			$environment_warning = self::get_environment_warning();

			if ( $environment_warning && is_plugin_active( plugin_basename( __FILE__ ) ) ) {
				$this->add_admin_notice( 'bad_environment', 'error', $environment_warning );
				return;
			}

			// If username or secret key are missing, prompt to go to settings.
			$gateway_settings = get_option( 'woocommerce_mistertango_settings', false );
			$is_plugin_section = isset( $_GET['page'], $_GET['section'] ) && 'wc-settings' === $_GET['page'] && $this->get_setting_section_slug() === $_GET['section'];

			if ( ( false === $gateway_settings || ( 'yes' === $gateway_settings['enabled'] && ( empty( $gateway_settings['username'] ) || empty( $gateway_settings['secret_key'] ) ) ) ) && ! $is_plugin_section ) {
				$this->add_admin_notice( 'prompt_connect', 'warning', sprintf( __( 'You are almost ready to accept payments. To get started, %1$sset your username and secret key%2$s.', 'woo-mistertango' ), '<a href="' . esc_url( $this->get_setting_link() ) . '">', '</a>' ) );
			}
		}

		/**
		 * Checks the environment for compatibility problems. Returns a string
		 * with the first incompatibility found or false if the environment has
		 * no problems.
		 *
		 * @since 3.0.0
		 */
		public static function get_environment_warning() {
			if ( version_compare( phpversion(), WC_MISTERTANGO_MIN_PHP, '<' ) ) {
				return sprintf( esc_html__( 'The minimum %1$s version required for this plugin is %2$s.', 'woo-mistertango' ), 'PHP', WC_MISTERTANGO_MIN_PHP ) . ' ' . sprintf( esc_html__( 'You are running version %s.', 'woo-mistertango' ), phpversion() );
			}

			if ( ! function_exists( 'mcrypt_encrypt' ) ) {
				return sprintf( esc_html__( 'The plugin requires %s to be installed on your server.', 'woo-mistertango' ), 'Mcrypt' );
			}

			if ( version_compare( $GLOBALS['wp_version'], WC_MISTERTANGO_MIN_WP, '<' ) ) {
				return sprintf( esc_html__( 'The minimum %1$s version required for this plugin is %2$s.', 'woo-mistertango' ), 'WordPress', WC_MISTERTANGO_MIN_WP ) . ' ' . sprintf( esc_html__( 'You are running version %s.', 'woo-mistertango' ), $GLOBALS['wp_version'] );
			}

			if ( ! defined( 'WC_VERSION' ) ) {
				return sprintf( esc_html__( 'The plugin requires %s plugin to be activated.', 'woo-mistertango' ), 'WooCommerce' );
			}

			if ( version_compare( WC_VERSION, WC_MISTERTANGO_MIN_WC, '<' ) ) {
				return sprintf( esc_html__( 'The minimum %1$s version required for this plugin is %2$s.', 'woo-mistertango' ), 'WooCommerce', WC_MISTERTANGO_MIN_WC ) . ' ' . sprintf( esc_html__( 'You are running version %s.', 'woo-mistertango' ), WC_VERSION );
			}

			return false;
		}

		/**
		 * Adds plugin action links.
		 *
		 * @since 3.0.0
		 */
		public function plugin_action_links( $actions, $plugin_file, $plugin_data, $context ) {
			return array_merge( array(
				'<a href="' . esc_url( $this->get_setting_link() ) . '">' . esc_html__( 'Settings', 'woo-mistertango' ) . '</a>',
			), $actions );
		}

		/**
		 * Adds plugin row meta.
		 *
		 * @since 3.1.3 Different support URLs based on locale.
		 * @since 3.0.0
		 */
		public function plugin_row_meta( $plugin_meta, $plugin_file, $plugin_data, $status ) {
			if ( plugin_basename( __FILE__ ) == $plugin_file ) {
				$plugin_meta[] = '<a href="' . esc_url( WC_MISTERTANGO_URL_PLUGIN_SUPPORT ) . '" target="_blank">' . esc_html__( 'Plugin support', 'woo-mistertango' ) . '</a>';

				// Different support URLs based on locale.
				$support_url = WC_MISTERTANGO_URL_CLIENT_SUPPORT;
				list( $lang ) = explode( '_', get_locale() );

				if ( 'lt' == $lang ) {
					$support_url = WC_MISTERTANGO_URL_CLIENT_SUPPORT_LT;
				}

				$plugin_meta[] = '<a href="' . esc_url( $support_url ) . '" target="_blank">' . esc_html__( 'Client support', 'woo-mistertango' ) . '</a>';
			}

			return $plugin_meta;
		}

		/**
		 * Get setting section link.
		 *
		 * @since 3.0.0
		 */
		public function get_setting_link() {
			return admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . $this->get_setting_section_slug() );
		}

		/**
		 * Get setting section slug.
		 *
		 * @since 3.0.0
		 */
		public function get_setting_section_slug() {
			return version_compare( WC_VERSION, '2.6', '>=' ) ? 'mistertango' : strtolower( 'WC_Gateway_Mistertango' );
		}

		/**
		 * Allow this class and other classes to add slug keyed notices (to avoid
		 * duplication).
		 *
		 * @since 3.0.0
		 */
		public function add_admin_notice( $slug, $class, $message ) {
			$this->notices[ $slug ] = array(
				'class'   => $class,
				'message' => $message,
			);
		}

		/**
		 * Display any notices we've collected thus far.
		 *
		 * @since 3.0.0
		 */
		public function admin_notices() {
			$notice_classes = array(
				'success'	=> 'notice notice-success',
				'error'		=> 'notice notice-error',
				'warning'	=> 'notice notice-warning',
				'info'		=> 'notice notice-info',
			);

			foreach ( $this->notices as $notice_key => $notice ) {
				$notice_message = sprintf( '%1$s %2$s', '<strong>' . WC_MISTERTANGO_NAME . ':</strong>', $notice['message'] );

				echo '<div class="' . esc_attr( $notice_classes[$notice['class']] ) . '"><p>';

				echo wp_kses( $notice_message, array(
					'a' => array( 'href' => array() ),
					'strong' => array(),
				) );

				echo '</p></div>';
			}
		}

		/**
		 * Initialize the gateway. Called very early - in the context of the
		 * plugins_loaded action.
		 *
		 * @since 3.0.0
		 */
		public function init_gateway() {
			if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
				return;
			}

			require_once( WC_MISTERTANGO_PATH . '/includes/class-wc-gateway-mistertango.php' );

			load_plugin_textdomain( 'woo-mistertango', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );

			add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateway' ) );
		}

		/**
		 * Add the gateway to WooCommerce.
		 *
		 * @since 3.0.0
		 */
		public function add_gateway( $methods ) {
			$methods[] = 'WC_Gateway_Mistertango';

			return $methods;
		}
	}

	/**
	 * Run the plugin instance.
	 *
	 * @since 3.0.0
	 */
	$GLOBALS['wc_plugin_mistertango'] = WC_Plugin_Mistertango::get_instance();
}
