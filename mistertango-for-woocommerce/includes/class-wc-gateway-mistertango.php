<?php
/**
* @package WC_Mistertango_Plugin
* @author NovaTemple
*/

if ( ! defined( 'ABSPATH' ) ) {
 exit;
}

/**
 * WC_Gateway_Mistertango class.
 *
 * @extends WC_Payment_Gateway
 */
class WC_Gateway_Mistertango extends WC_Payment_Gateway {
	/**
	 * Constructor for the gateway.
	 */
 	public function __construct() {
 		$this->id                 = 'mistertango';
 		$this->has_fields         = false;
 		$this->method_title       = 'Mistertango';
 		$this->method_description = sprintf( __( '%1$sSign up%2$s for Mistertango account and get your username and secret key. If you need assistance, follow instructions on %3$ssupport website%4$s.', 'mistertango-woocommerce' ), '<a href="' . esc_url( WC_MISTERTANGO_URL_WEBSITE ) . '" target="_blank">', '</a>', '<a href="' . esc_url( WC_MISTERTANGO_URL_SUPPORT_CLIENT ) . '" target="_blank">', '</a>' );
 		$this->order_button_text  = __( 'Checkout', 'mistertango-woocommerce' );
 		$this->supports           = array(
 			'products',
 		);

		/**
		 * Load icon.
		 */
 		$this->icon = apply_filters( 'woocommerce_mistertango_icon', '' );

		/**
		 * Load settings.
		 */
 		$this->init_form_fields();
 		$this->init_settings();

		/**
		 * Define user set options.
		 */
 		$this->enabled								= $this->get_option( 'enabled', 'yes' );
 		$this->title									= $this->get_option( 'title', __( 'Bank transfer, credit card and other', 'mistertango-woocommerce' ) );
 		$this->description						= $this->get_option( 'description', __( 'Payments collected by Mistertango.', 'mistertango-woocommerce' ) );
 		$this->username								= $this->get_option( 'username', '' );
 		$this->secret_key							= $this->get_option( 'secret_key', '' );
 		$this->market									= $this->get_option( 'market', 'LT' );
 		$this->mistertango_language		= $this->get_option( 'mistertango_language', 'lt' );
 		$this->auto_detect_language		= $this->get_option( 'auto_detect_language', 'no' );
 		$this->overwrite_callback_url	= $this->get_option( 'overwrite_callback_url', 'no' );
 		$this->log										= $this->get_option( 'log', 'no' );

 		if ( defined( 'DOING_AJAX' ) && DOING_AJAX && $this->is_available() ) {
			$this->order_button_text = sprintf( '%1$s %2$s', $this->order_button_text, strip_tags( wc_price( $this->get_order_total() ) ) );
 		}

		/**
		 * Save options on backend.
		 */
 		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

		/**
		 * Load scripts on frontend.
		 */
 		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		/**
		 * Action for callback check.
		 */
 		add_action( 'woocommerce_api_' . strtolower( get_class( $this ) ), array( $this, 'payment_callback' ) );

		/**
		 * Action for thankyou message after payment.
		 */
 		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou' ) );
 	}

	/**
	 * Log message.
	 */
	public function log( $message ) {
		if ( 'yes' === $this->log ) {
			WC_Plugin_Mistertango::log( $message );
		}
	}

	/**
	 * Check if gateway is enabled and has required fields filled in.
	 */
 	public function is_available() {
 		if ( 'yes' === $this->enabled && ! empty( $this->username ) && ! empty( $this->secret_key ) ) {
 			return true;
 		}

 		return false;
 	}

	/**
	 * Show gateway settings block.
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
	 */
 	public function init_form_fields() {
 		$this->form_fields = require( WC_MISTERTANGO_PATH . '/includes/settings-mistertango.php' );
 	}

	/**
	 * Load frontend scripts.
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
	 * Payment fields for payment window with gateway description before it.
	 */
	public function payment_fields() {
		if ( $description = $this->get_description() ) {
 			echo wpautop( wptexturize( $description ) );
 		}

		echo '<div id="mistertango-payment-data-holder" style="display: none;"></div>';
	}

	/**
	 * Generate order and payment form for window.
	 */
 	public function process_payment( $order_id ) {
		$this->log( sprintf( 'Order #%1$s: generating payment request form.', $order_id ) );

		$order = wc_get_order( $order_id );
		$order_description = $this->get_order_description( $order_id );

		$payment_window_lang = $this->mistertango_language;

		if ( 'yes' === $this->auto_detect_language ) {
			$languages = array( 'lt', 'en', 'lv', 'et', 'ru', 'fi', 'fr', 'nl', 'it', 'es', 'uk', 'hu', 'ro', 'bg', 'cs', 'sk', 'de', );
	 		list( $lang ) = explode( '_', get_locale() );

			if ( in_array( $lang, $languages ) ) {
				$payment_window_lang = $lang;
			}
		}

		$encrypted_callback = '';

		if ( 'yes' === $this->overwrite_callback_url ) {
			$encrypted_callback = $this->encrypt( WC()->api_request_url( get_class( $this ) ), $this->secret_key );
		}

		$payment_form_in = array(
			'data-recipient'		=> esc_attr( $this->username ),
			'data-lang'					=> esc_attr( $payment_window_lang ),
			'data-payer'				=> esc_attr( version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->billing_email : $order->get_billing_email() ),
			'data-amount'				=> esc_attr( number_format( $order->get_total(), 2, '.', ',' ) ),
			'data-currency'			=> esc_attr( version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->get_order_currency() : $order->get_currency() ),
			'data-description'	=> esc_attr( $order_description ),
			'data-callback'			=> esc_attr( $encrypted_callback ),
			'data-return'				=> esc_attr( $this->get_return_url( $order ) ),
			'data-market'				=> esc_attr( $this->market ),
		);

		$payment_form_out = implode( ' ', array_map(
			function( $v, $k ) {
				return sprintf( '%s="%s"', $k, $v );
			},
			$payment_form_in,
			array_keys( $payment_form_in )
		));

		$return_data = array(
			'result'				=> 'success',
			'order_id'			=> $order_id,
			'payment_form'	=> sprintf( '<div id="mistertango-payment-data" %1$s></div>', $payment_form_out ),
		);

		echo json_encode( $return_data );
		exit;
 	}

	/**
	 * Handle payment callback.
	 */
 	public function payment_callback( $request ) {
 		try {
			if ( empty( $_POST ) || ! isset( $_POST['hash'] ) ) {
				$this->log( 'Payment callback: empty request.' );

				throw new Exception( 'Payment callback: empty request.' );
			}

			$hash = json_decode( $this->decrypt( $_POST['hash'], $this->secret_key ), true );

			if ( empty( $hash ) ) {
				$this->log( 'Payment callback: hash decryption failed.' );

				throw new Exception( 'Payment callback: hash decryption failed.' );
			}

			$response = json_decode( $hash['custom'], true );

			if ( empty( $response ) ) {
				$this->log( 'Payment callback: empty custom body.' );

				throw new Exception( 'Payment callback: empty custom body.' );
			}

 			if ( $response['status'] == 'paid' && $response['data']['status'] == 'CONFIRMED' ) {
				list( $order_prefix, $order_id ) = explode( ' ', $response['description'] );
				$order_id = absint( $order_id );

 				$order = wc_get_order( $order_id );

 				$order_currency = version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->get_order_currency() : $order->get_currency();
 				$completed_status = version_compare( WC_VERSION, '3.0.0', '<' ) ? apply_filters( 'woocommerce_order_is_paid_statuses', array( 'processing', 'completed' ) ) : wc_get_is_paid_statuses();

 				if ( $order->has_status( $completed_status ) ) {
 					$this->log( sprintf( 'Order #%1$s: is already paid (%2$s).', $order_id, $order->get_status() ) );

 					throw new Exception( sprintf( 'Order #%1$s: is already paid (%2$s).', $order_id, $order->get_status() ) );
 				}

 				if ( $order->get_total() != $response['data']['amount'] ) {
 					$this->log( sprintf( 'Order #%1$s: amounts do no match (%2$s != %3$s).', $order_id, $order->get_total(), $response['data']['amount'] ) );

 					throw new Exception( sprintf( 'Order #%1$s: amounts do no match (%2$s != %3$s).', $order_id, $order->get_total(), $response['data']['amount'] ) );
 				}

 				if ( $order_currency != $response['data']['currency'] ) {
 					$this->log( sprintf( 'Order #%1$s: currencies do not match (%2$s != %3$s).', $order_id, $order_currency, $response['data']['currency'] ) );

 					throw new Exception( sprintf( 'Order #%1$s: currencies do not match (%2$s != %3$s).', $order_id, $order_currency, $response['data']['currency'] ) );
 				}

 				$this->log( sprintf( __( '%1$s: payment callback completed (%2$s) via %3$s.', 'mistertango-woocommerce' ), $this->method_title, $response['invoice'], $response['type'] ) );

 				$order->add_order_note( sprintf( __( '%1$s: payment callback completed (%2$s) via %3$s.', 'mistertango-woocommerce' ), $this->method_title, $response['invoice'], $response['type'] ) );
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
	 * Thankyou message after payment.
	 */
 	public function thankyou( $order_id ) {
 		echo '';
 	}

	/**
	 * Get site slug for usage in order description as prefix.
	 */
	public function get_order_prefix() {
		return sanitize_title_with_dashes( parse_url( home_url(), PHP_URL_HOST ) );
	}

	/**
	 * Generate order description for usage in payment form and callback.
	 */
	public function get_order_description( $order_id ) {
		return sprintf( '%1$s %2$s', $this->get_order_prefix(), $order_id );
	}

	/**
	 * Encryption function for callback.
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
