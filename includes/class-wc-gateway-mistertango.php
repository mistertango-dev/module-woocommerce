<?php
/**
* @package WC_Plugin_Mistertango
* @author NovaTemple
*
* @since 3.0.0
*/

if ( ! defined( 'ABSPATH' ) ) {
 exit;
}

/**
 * WC_Gateway_Mistertango class.
 *
 * @class WC_Gateway_Mistertango
 * @extends WC_Payment_Gateway
 *
 * @since 3.0.0
 */
class WC_Gateway_Mistertango extends WC_Payment_Gateway {
	/**
	 * Order description separator.
	 *
	 * @since 3.1.4
	 */
	public $order_description_separator = ' ';

	/**
	 * Constructor for the gateway.
	 *
	 * @since 3.1.6 Payment button text suffix for manually created orders.
	 * @since 3.1.4 Action for support of manually created orders.
	 * @since 3.1.3 Different support URLs based on locale.
	 * @since 3.0.0
	 */
 	public function __construct() {
		// Different support URLs based on locale.
		$signup_url = WC_MISTERTANGO_URL_WEBSITE;
		$support_url = WC_MISTERTANGO_URL_CLIENT_SUPPORT;

		list( $lang ) = explode( '_', get_locale() );

		if ( 'lt' == $lang ) {
			$signup_url = WC_MISTERTANGO_URL_WEBSITE_LT;
			$support_url = WC_MISTERTANGO_URL_CLIENT_SUPPORT_LT;
		}

		// General payment gateway variables.
 		$this->id                 = 'mistertango';
 		$this->has_fields         = false;
 		$this->method_title       = 'Mistertango';
 		$this->method_description = wp_kses( sprintf( __( '%1$sSign up%2$s for Mistertango account and get your username and secret key. If you need assistance, follow instructions on %3$ssupport website%4$s.', 'woo-mistertango' ), '<a href="' . esc_url( $signup_url ) . '" target="_blank">', '</a>', '<a href="' . esc_url( $support_url ) . '" target="_blank">', '</a>' ), array(
			'a' => array( 'href' => array(), 'target' => array() ),
		) );
 		$this->order_button_text  = esc_html__( 'Checkout', 'woo-mistertango' );
 		$this->supports           = array(
 			'products',
 		);

		// Load settings.
 		$this->init_form_fields();
 		$this->init_settings();

		// Define user set options.
 		$this->enabled                 = $this->get_option( 'enabled', 'yes' );
 		$this->title                   = $this->get_option( 'title', esc_html__( 'Bank transfer, credit card and other', 'woo-mistertango' ) );
 		$this->description             = $this->get_option( 'description', esc_html__( 'Payments collected by Mistertango.', 'woo-mistertango' ) );
 		$this->username                = $this->get_option( 'username', '' );
 		$this->secret_key              = $this->get_option( 'secret_key', '' );
 		$this->market                  = $this->get_option( 'market', 'LT' );
 		$this->mistertango_language    = $this->get_option( 'mistertango_language', 'lt' );
 		$this->auto_detect_language    = $this->get_option( 'auto_detect_language', 'no' );
 		$this->overwrite_callback_url  = $this->get_option( 'overwrite_callback_url', 'yes' );
 		$this->log                     = $this->get_option( 'log', 'no' );

		// Load icon.
 		$this->icon = apply_filters( 'woocommerce_mistertango_icon', '' );

		// Custom checkout button text suffix with total amount.
		if ( $this->is_available() ) {
			$order_button_total = 0;

			if ( defined( 'DOING_AJAX' ) && DOING_AJAX && $this->get_order_total() ) {
				$order_button_total = $this->get_order_total();
	 		}
			elseif ( is_checkout_pay_page() && $order_id = absint( get_query_var( 'order-pay' ) ) ) {
				$order = wc_get_order( $order_id );
				$order_button_total = $order->get_total();
	 		}

			if ( 0 < $order_button_total ) {
				$this->order_button_text = sprintf( '%1$s %2$s', $this->order_button_text, strip_tags( wc_price( $order_button_total ) ) );
			}
		}

		// Action for loading scripts on frontend.
 		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10, 0 );

		// Action for saving options on backend.
 		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ), 10, 0 );

		// Action for support of manually created orders.
		add_action( 'after_woocommerce_pay', array( $this, 'process_manual_payment' ), 10, 0 );

		// Action for thankyou message after payment.
 		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou' ), 10, 1 );

		// Action for callback check.
 		add_action( 'woocommerce_api_' . strtolower( get_class( $this ) ), array( $this, 'payment_callback' ), 10, 0 );
 	}

	/**
	 * Log message.
	 *
	 * @since 3.0.0
	 */
	public function log( $message ) {
		if ( 'yes' === $this->log ) {
			WC_Plugin_Mistertango::log( $message );
		}
	}

	/**
	 * Check if the gateway is enabled and has required fields filled in.
	 *
	 * @since 3.0.0
	 */
 	public function is_available() {
 		if ( 'yes' === $this->enabled && ! empty( $this->username ) && ! empty( $this->secret_key ) ) {
 			return true;
 		}

 		return false;
 	}

	/**
	 * Load frontend scripts.
	 *
	 * @since 3.0.0
	 */
 	public function enqueue_scripts() {
 		if ( ! is_checkout() || ! $this->is_available() ) {
			return;
 		}

 		wp_enqueue_script(
 			'wc-mistertango-api',
 			WC_MISTERTANGO_URL_API_JS,
 			array( 'jquery' ),
 			time(),
 			true
 		);

 		wp_enqueue_script(
 			'wc-mistertango-checkout',
 			WC_MISTERTANGO_URL . '/assets/js/checkout.js',
 			array( 'jquery', 'wc-mistertango-api' ),
 			WC_MISTERTANGO_VERSION,
 			true
 		);
 	}

	/**
	 * Show the gateway settings block.
	 *
	 * @since 3.0.0
	 */
 	public function admin_options() {
 		?>
 		<h2><?php echo $this->method_title; ?></h2>
 		<p><?php echo $this->method_description; ?></p>
 		<table class="form-table">
 		<?php $this->generate_settings_html(); ?>
 		</table>
 		<?php
 	}

	/**
	 * Show settings fields.
	 *
	 * @since 3.0.0
	 */
 	public function init_form_fields() {
 		$this->form_fields = require( WC_MISTERTANGO_PATH . '/includes/settings-mistertango.php' );
 	}

	/**
	 * Payment fields for the payment window with the gateway description before it.
	 *
	 * @since 3.0.0
	 */
	public function payment_fields() {
		if ( $description = $this->get_description() ) {
 			echo wpautop( wptexturize( $description ) );
 		}

		if ( is_checkout() && ! is_checkout_pay_page() ) {
			echo '<div id="mistertango-payment-data-holder" style="display: none;"></div>';
		}
	}

	/**
	 * Thankyou message after payment.
	 *
	 * @since 3.0.0
	 */
 	public function thankyou( $order_id ) {
 		echo '';
 	}

	/**
	 * Initialize order and payment form for window.
	 *
	 * @since 3.1.4 Support for manually created orders.
	 * @since 3.0.0
	 */
 	public function process_payment( $order_id, $return_result = false ) {
		$this->log( sprintf( 'Order #%s: generating payment request form.', $order_id ) );

		$order = wc_get_order( $order_id );
		$order_description = $this->get_order_description( $order_id );

		$payment_window_lang = $this->mistertango_language;

		// Auto detect locale for the payment window.
		if ( 'yes' === $this->auto_detect_language ) {
			$languages = array( 'lt', 'en', 'lv', 'et', 'ru', 'fi', 'fr', 'nl', 'it', 'es', 'uk', 'hu', 'ro', 'bg', 'cs', 'sk', 'de', );
	 		list( $lang ) = explode( '_', get_locale() );

			if ( in_array( $lang, $languages ) ) {
				$payment_window_lang = $lang;
			}
		}

		// Overwrite default callback URL.
		$encrypted_callback = '';

		if ( 'yes' === $this->overwrite_callback_url ) {
			$encrypted_callback = $this->encrypt( WC()->api_request_url( get_class( $this ) ), $this->secret_key );
		}

		$payment_form_in = array(
			'data-recipient'      => esc_attr( $this->username ),
			'data-lang'           => esc_attr( $payment_window_lang ),
			'data-payer'          => esc_attr( version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->billing_email : $order->get_billing_email() ),
			'data-amount'         => esc_attr( $order->get_total() ),
			'data-currency'       => esc_attr( version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->get_order_currency() : $order->get_currency() ),
			'data-description'    => esc_attr( $order_description ),
			'data-callback'       => esc_attr( $encrypted_callback ),
			'data-return'         => esc_attr( $this->get_return_url( $order ) ),
			'data-market'         => esc_attr( $this->market ),
		);

		$this->log( sprintf( 'Order #%1$s: generated payment request form attributes: %2$s', $order_id, print_r( $payment_form_in, true ) ) );

		$payment_form_out = implode( ' ', array_map(
			function( $v, $k ) {
				return sprintf( '%1$s="%2$s"', $k, $v );
			},
			$payment_form_in,
			array_keys( $payment_form_in )
		));

		$payment_form = sprintf( '<div id="mistertango-payment-data" %s></div>', $payment_form_out );

		$return_data = json_encode( array(
			'result'          => 'success',
			'order_id'        => $order_id,
			'payment_form'    => $payment_form,
		) );

		$this->log( sprintf( 'Order #%1$s: generated payment request form: %2$s', $order_id, $payment_form ) );

		if ( $return_result ) {
			return $return_data;
		}

		echo $return_data;
		exit;
 	}

	/**
	 * Initialize order and payment form for window of manually created order.
	 *
	 * @since 3.1.4
	 */
	public function process_manual_payment() {
		if ( is_checkout_pay_page() && $order_id = absint( get_query_var( 'order-pay' ) ) ) {
			$payment_form_data = json_decode( $this->process_payment( $order_id, true ), true );

			if( ! empty( $payment_form_data ) && isset( $payment_form_data['result'], $payment_form_data['payment_form'] ) && 'success' === $payment_form_data['result'] ) {
				echo sprintf( '<div id="mistertango-payment-data-holder" style="display: none;">%s</div>', $payment_form_data['payment_form'] );
			}
		}
	}

	/**
	 * Handle payment callback.
	 *
	 * @since 3.0.0
	 */
 	public function payment_callback() {
 		try {
			$this->log( sprintf( 'Received payment callback: %s', print_r( $_POST, true ) ) );

			if ( empty( $_POST ) || ! isset( $_POST['callback_uuid'], $_POST['hash'] ) ) {
				throw new Exception( 'payment callback is empty.' );
			}

			$hash = json_decode( $this->decrypt( $_POST['hash'], $this->secret_key ), true );

			if ( empty( $hash ) ) {
				throw new Exception( 'payment callback - hash decryption failed.' );
			}

			$response = json_decode( $hash['custom'], true );

			if ( empty( $response ) ) {
				throw new Exception( 'payment callback - empty custom entry.' );
			}

			$this->log( sprintf( 'Decrypted payment callback: %s', print_r( $response, true ) ) );

			$this->log( sprintf( 'Processing payment callback (uuid: %s).', $_POST['callback_uuid'] ) );

			$order_description = explode( $this->order_description_separator, $response['description'] );

			if ( 2 > count( $order_description ) ) {
				throw new Exception( sprintf( 'incorrect order description (description: %s).', $response['description'] ) );
			}

			$order = wc_get_order( absint( $order_description[1] ) );

			if ( false === $order ) {
				throw new Exception( sprintf( 'order not found (description: %s).', $response['description'] ) );
			}

			$order_id = version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->id : $order->get_id();
			$order_currency = version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->get_order_currency() : $order->get_currency();
			$completed_status = version_compare( WC_VERSION, '3.0.0', '<' ) ? apply_filters( 'woocommerce_order_is_paid_statuses', array( 'processing', 'completed' ) ) : wc_get_is_paid_statuses();

			$this->log( sprintf( 'Order #%s: validating payment callback.', $order_id ) );

			if ( $order->has_status( $completed_status ) ) {
				$this->log( sprintf( 'Order #%1$s is already paid (%2$s).', $order_id, $order->get_status() ) );

				echo 'OK';
			}
			else {
				if ( $order->get_total() != $response['data']['amount'] ) {
					throw new Exception( sprintf( 'order #%1$s amounts do no match (%2$s != %3$s).', $order_id, $order->get_total(), $response['data']['amount'] ) );
				}

				if ( $order_currency != $response['data']['currency'] ) {
					throw new Exception( sprintf( 'order #%1$s currencies do not match (%2$s != %3$s).', $order_id, $order_currency, $response['data']['currency'] ) );
				}

				$this->log( sprintf( 'Order #%1$s payment callback is valid and payment received via %2$s (invoice: %3$s).', $order_id, $response['type'], $response['invoice'] ) );

				$order->add_order_note( sprintf( esc_html__( '%1$s: order #%2$s payment received via %3$s (invoice: %4$s).', 'woo-mistertango' ), $this->method_title, $order_id, $response['type'], $response['invoice'] ) );
				$order->payment_complete();

				echo 'OK';
			}
 		} catch ( Exception $e ) {
 			$msg = sprintf( '%1$s: %2$s', get_class( $e ), $e->getMessage() );

 			$this->log( $msg );

 			echo $msg;
 		}

		exit;
 	}

	/**
	 * Get site slug for usage in order description as prefix.
	 *
	 * @since 3.0.0
	 */
	public function get_order_prefix() {
		return sanitize_title_with_dashes( parse_url( home_url(), PHP_URL_HOST ) );
	}

	/**
	 * Generate order description for usage in payment form and callback.
	 *
	 * @since 3.0.0
	 */
	public function get_order_description( $order_id ) {
		return $this->get_order_prefix() . $this->order_description_separator . $order_id;
	}

	/**
	 * Encryption function for callback.
	 *
	 * @since 3.0.0
	 */
	public function encrypt( $plain_text, $key ) {
	  $key = str_pad( $key, 32, "\0" );

	  $plain_text = trim( $plain_text );
	  $iv_size = mcrypt_get_iv_size( MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC );
	  $iv = mcrypt_create_iv( $iv_size, MCRYPT_RAND );

	  $ciphertext = mcrypt_encrypt( MCRYPT_RIJNDAEL_128, $key, $plain_text, MCRYPT_MODE_CBC, $iv );

	  $ciphertext = $iv . $ciphertext;

	  $sResult = base64_encode( $ciphertext );
	  return trim( $sResult );
	}

	/**
	 * Decryption function for callback.
	 *
	 * @since 3.0.0
	 */
	public function decrypt( $encoded_text, $key ) {
	  $key = str_pad( $key, 32, "\0" );

	  $encoded_text = trim( $encoded_text );
	  $ciphertext_dec = base64_decode( $encoded_text );

	  $iv_size = mcrypt_get_iv_size( MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC );

	  $iv_dec = substr( $ciphertext_dec, 0, $iv_size );

	  $ciphertext_dec = substr( $ciphertext_dec, $iv_size );

	  $sResult = mcrypt_decrypt( MCRYPT_RIJNDAEL_128, $key, $ciphertext_dec, MCRYPT_MODE_CBC, $iv_dec );
	  return trim( $sResult );
	}
}
