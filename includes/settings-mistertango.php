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
 * Settings for Mistertango payment gateway.
 *
 * @since 3.0.0
 */
return array(
	'enabled'	=> array(
		'type'          => 'checkbox',
		'title'         => esc_html__( 'Enable/Disable', 'woo-mistertango' ),
		'label'         => esc_html__( 'Enable Mistertango', 'woo-mistertango' ),
		'description'   => '',
		'default'       => 'yes',
	),
	'title'	=> array(
		'type'        	=> 'text',
		'title'       	=> esc_html__( 'Title', 'woo-mistertango' ),
		'description' 	=> esc_html__( 'This controls the title which the user sees during checkout.', 'woo-mistertango' ),
		'desc_tip'      => true,
		'placeholder' 	=> esc_html__( 'Bank transfer, credit card and other', 'woo-mistertango' ),
		'default'     	=> esc_html__( 'Bank transfer, credit card and other', 'woo-mistertango' ),
	),
	'description'	=> array(
		'type'        	=> 'textarea',
		'title'       	=> esc_html__( 'Description', 'woo-mistertango' ),
		'description' 	=> esc_html__( 'This controls the description which the user sees during checkout.', 'woo-mistertango' ),
		'desc_tip'      => true,
		'placeholder' 	=> esc_html__( 'Payments collected by Mistertango.', 'woo-mistertango' ),
		'default'     	=> esc_html__( 'Payments collected by Mistertango.', 'woo-mistertango' ),
	),
	'username'	=> array(
		'type'        	=> 'text',
		'title'       	=> esc_html__( 'Username', 'woo-mistertango' ),
		'description' 	=> esc_html__( 'Mistertango username.', 'woo-mistertango' ),
		'desc_tip'      => true,
		'placeholder' 	=> esc_html__( 'Username', 'woo-mistertango' ),
		'default'     	=> '',
	),
	'secret_key'	=> array(
		'type'        	=> 'text',
		'title'       	=> esc_html__( 'Secret Key', 'woo-mistertango' ),
		'description'   => esc_html__( 'Mistertango secret key.', 'woo-mistertango' ),
		'desc_tip'      => true,
		'placeholder' 	=> esc_html__( 'Secret Key', 'woo-mistertango' ),
		'default'     	=> '',
	),
	'market'	=> array(
		'type'        	=> 'select',
		'title'       	=> esc_html__( 'Default Country', 'woo-mistertango' ),
		'description' 	=> esc_html__( 'By default, show payment methods available for the selected country only. Anyway, the user during checkout will have an ability to change the country by oneself.', 'woo-mistertango' ),
		'desc_tip'      => true,
		'default'     	=> 'LT',
		'options'       => array(
			'LT'	=> esc_html__( 'Lithuania', 'woo-mistertango' ),
			'LV'	=> esc_html__( 'Latvia', 'woo-mistertango' ),
			'EE'	=> esc_html__( 'Estonia', 'woo-mistertango' ),
			'FI'	=> esc_html__( 'Finland', 'woo-mistertango' ),
			'FR'	=> esc_html__( 'France', 'woo-mistertango' ),
			'NL'	=> esc_html__( 'Netherlands', 'woo-mistertango' ),
			'IT'	=> esc_html__( 'Italia', 'woo-mistertango' ),
			'ES'	=> esc_html__( 'Spain', 'woo-mistertango' ),
			'SK'	=> esc_html__( 'Slovakia', 'woo-mistertango' ),
			'DE'	=> esc_html__( 'Germany', 'woo-mistertango' ),
		),
	),
	'mistertango_language'	=> array(
		'type'        	=> 'select',
		'title'       	=> esc_html__( 'Default Language', 'woo-mistertango' ),
		'description' 	=> esc_html__( 'Default payment window language.', 'woo-mistertango' ),
		'desc_tip'      => true,
		'default'     	=> 'lt',
		'options'       => array(
			'lt'	=> esc_html__( 'Lithuanian', 'woo-mistertango' ),
			'en'	=> esc_html__( 'English', 'woo-mistertango' ),
			'lv'	=> esc_html__( 'Latvian', 'woo-mistertango' ),
			'et'	=> esc_html__( 'Estonian', 'woo-mistertango' ),
			'ru'	=> esc_html__( 'Russian', 'woo-mistertango' ),
			'fi'	=> esc_html__( 'Finnish', 'woo-mistertango' ),
			'fr'	=> esc_html__( 'French', 'woo-mistertango' ),
			'nl'	=> esc_html__( 'Dutch', 'woo-mistertango' ),
			'it'	=> esc_html__( 'Italian', 'woo-mistertango' ),
			'es'	=> esc_html__( 'Spanish', 'woo-mistertango' ),
			'uk'	=> esc_html__( 'Ukrainian', 'woo-mistertango' ),
			'hu'	=> esc_html__( 'Hungarian', 'woo-mistertango' ),
			'ro'	=> esc_html__( 'Romanian', 'woo-mistertango' ),
			'bg'	=> esc_html__( 'Bulgarian', 'woo-mistertango' ),
			'cs'	=> esc_html__( 'Czech', 'woo-mistertango' ),
			'sk'	=> esc_html__( 'Slovak', 'woo-mistertango' ),
			'de'	=> esc_html__( 'German', 'woo-mistertango' ),
		),
	),
	'auto_detect_language'	=> array(
		'type'        	=> 'checkbox',
		'title'       	=> esc_html__( 'Auto Detect Language', 'woo-mistertango' ),
		'label'       	=> esc_html__( 'Automatically detect website language', 'woo-mistertango' ),
		'description' 	=> esc_html__( 'Show the payment window in a website language. If the required language is not supported, it will falback to a default selected language. Enable this option if your website is multilingual.', 'woo-mistertango' ),
		'default'     	=> 'no',
	),
	'overwrite_callback_url'	=> array(
		'type'        	=> 'checkbox',
		'title'       	=> esc_html__( 'Overwrite Callback URL', 'woo-mistertango' ),
		'label'       	=> esc_html__( 'Overwrite default callback URL', 'woo-mistertango' ),
		'description' 	=> esc_html__( 'Mistertango API will ignore the default callback URL which is set on your account and will use a callback URL provided by the plugin. If you are using Mistertango on multiple websites, you should enable this option.', 'woo-mistertango' ),
		'default'     	=> 'yes',
	),
	'log'	=> array(
		'type'        	=> 'checkbox',
		'title'       	=> esc_html__( 'Debug Log', 'woo-mistertango' ),
		'label'       	=> esc_html__( 'Enable logging', 'woo-mistertango' ),
		'description' 	=> wp_kses( sprintf( __( 'Save debug messages to WooCommerce system status log. You can %1$sview logs here%2$s.', 'woo-mistertango' ), '<a href="' . esc_url( admin_url( 'admin.php?page=wc-status&tab=logs&log_file=mistertango-' . sanitize_file_name( wp_hash( 'mistertango' ) ) . '-log' ) ) . '">', '</a>' ), array(
			'a' => array( 'href' => array() ),
		) ),
		'default'     	=> 'no',
	),
);
