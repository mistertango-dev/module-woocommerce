<?php
/**
* @package WC_Mistertango_Plugin
* @author NovaTemple
*/

if ( ! defined( 'ABSPATH' ) ) {
 exit;
}

/**
 * Settings for Mistertango payment gateway.
 */
return array(
	'enabled'			=> array(
		'type'        => 'checkbox',
		'title'       => __( 'Enable/Disable', 'mistertango-woocommerce' ),
		'label'       => __( 'Enable Mistertango', 'mistertango-woocommerce' ),
		'description' => '',
		'default'     => 'yes',
	),
	'title'				=> array(
		'type'        => 'text',
		'title'       => __( 'Title', 'mistertango-woocommerce' ),
		'description' => __( 'This controls the title which the user sees during checkout.', 'mistertango-woocommerce' ),
		'desc_tip'		=> true,
		'placeholder' => __( 'Bank transfer, credit card and other', 'mistertango-woocommerce' ),
		'default'     => __( 'Bank transfer, credit card and other', 'mistertango-woocommerce' ),
	),
	'description'	=> array(
		'type'        => 'textarea',
		'title'       => __( 'Description', 'mistertango-woocommerce' ),
		'description' => __( 'This controls the description which the user sees during checkout.', 'mistertango-woocommerce' ),
		'desc_tip'		=> true,
		'placeholder' => __( 'Payments collected by Mistertango.', 'mistertango-woocommerce' ),
		'default'     => __( 'Payments collected by Mistertango.', 'mistertango-woocommerce' ),
	),
	'username'		=> array(
		'type'        => 'text',
		'title'       => __( 'Username', 'mistertango-woocommerce' ),
		'description' => __( 'Mistertango username.', 'mistertango-woocommerce' ),
		'desc_tip'		=> true,
		'placeholder' => __( 'Username', 'mistertango-woocommerce' ),
		'default'     => '',
	),
	'secret_key'	=> array(
		'type'        => 'text',
		'title'       => __( 'Secret Key', 'mistertango-woocommerce' ),
		'description' => __( 'Mistertango secret key.', 'mistertango-woocommerce' ),
		'desc_tip'		=> true,
		'placeholder' => __( 'Secret Key', 'mistertango-woocommerce' ),
		'default'     => '',
	),
	'market'			=> array(
		'type'        => 'select',
		'title'       => __( 'Country', 'mistertango-woocommerce' ),
		'description' => __( 'Show payment methods only available for selected country.', 'mistertango-woocommerce' ),
		'desc_tip'		=> true,
		'default'     => 'LT',
		'options'			=> array(
			'LT'	=> __( 'Lithuania', 'mistertango-woocommerce' ),
			'LV'	=> __( 'Latvia', 'mistertango-woocommerce' ),
			'EE'	=> __( 'Estonia', 'mistertango-woocommerce' ),
			'FI'	=> __( 'Finland', 'mistertango-woocommerce' ),
			'FR'	=> __( 'France', 'mistertango-woocommerce' ),
			'NL'	=> __( 'Netherlands', 'mistertango-woocommerce' ),
			'IT'	=> __( 'Italia', 'mistertango-woocommerce' ),
			'ES'	=> __( 'Spain', 'mistertango-woocommerce' ),
			'SK'	=> __( 'Slovakia', 'mistertango-woocommerce' ),
			'DE'	=> __( 'Germany', 'mistertango-woocommerce' ),
		),
	),
	'mistertango_language'	=> array(
		'type'        => 'select',
		'title'       => __( 'Language', 'mistertango-woocommerce' ),
		'description' => __( 'Payment window language.', 'mistertango-woocommerce' ),
		'desc_tip'		=> true,
		'default'     => 'lt',
		'options'			=> array(
			'lt'	=> __( 'Lithuanian', 'mistertango-woocommerce' ),
			'en'	=> __( 'English', 'mistertango-woocommerce' ),
			'lv'	=> __( 'Latvian', 'mistertango-woocommerce' ),
			'et'	=> __( 'Estonian', 'mistertango-woocommerce' ),
			'ru'	=> __( 'Russian', 'mistertango-woocommerce' ),
			'fi'	=> __( 'Finnish', 'mistertango-woocommerce' ),
			'fr'	=> __( 'French', 'mistertango-woocommerce' ),
			'nl'	=> __( 'Dutch', 'mistertango-woocommerce' ),
			'it'	=> __( 'Italian', 'mistertango-woocommerce' ),
			'es'	=> __( 'Spanish', 'mistertango-woocommerce' ),
			'uk'	=> __( 'Ukrainian', 'mistertango-woocommerce' ),
			'hu'	=> __( 'Hungarian', 'mistertango-woocommerce' ),
			'ro'	=> __( 'Romanian', 'mistertango-woocommerce' ),
			'bg'	=> __( 'Bulgarian', 'mistertango-woocommerce' ),
			'cs'	=> __( 'Czech', 'mistertango-woocommerce' ),
			'sk'	=> __( 'Slovak', 'mistertango-woocommerce' ),
			'de'	=> __( 'German', 'mistertango-woocommerce' ),
		),
	),
	'auto_detect_language'			=> array(
		'type'        => 'checkbox',
		'title'       => __( 'Auto Detect Language', 'mistertango-woocommerce' ),
		'label'       => __( 'Automatically detect website language', 'mistertango-woocommerce' ),
		'description' => __( 'Enable this option if your website is multilingual. If the required language is not supported, it will falback to default language which is selected above.', 'mistertango-woocommerce' ),
		'default'     => 'no',
	),
	'overwrite_callback_url'	=> array(
		'type'        => 'checkbox',
		'title'       => __( 'Overwrite Callback URL', 'mistertango-woocommerce' ),
		'label'       => __( 'Overwrite default callback URL by ignoring it', 'mistertango-woocommerce' ),
		'description' => __( 'If you are using Mistertango on multiple websites, you should enable this option.', 'mistertango-woocommerce' ),
		'default'     => 'no',
	),
	'log'					=> array(
		'type'        => 'checkbox',
		'title'       => __( 'Debug Log', 'mistertango-woocommerce' ),
		'label'       => __( 'Enable logging', 'mistertango-woocommerce' ),
		'description' => sprintf( __( 'Save debug messages to WooCommerce System Status log. You can view logs %1$shere%2$s.', 'mistertango-woocommerce' ), '<a href="' . esc_url( admin_url( 'admin.php?page=wc-status&tab=logs&log_file=mistertango-' . sanitize_file_name( wp_hash( 'mistertango' ) ) . '-log' ) ) . '">', '</a>' ),
		'default'     => 'no',
	),
);
