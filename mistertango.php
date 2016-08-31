<?php
/*
Plugin Name: WooCommerce Mistertango Payment
Plugin URI: https://mistertango.lt/
Description: WooCommerce Mistertango Payment module
Version: 2.1
Author: thairesearchinfotech.com
Author URI: https://www.thairesearchinfotech.com/

Copyright: © 2015 mistertango
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

$plugin_path = plugin_dir_url("mistertango.php") . basename(__DIR__);

define("PLUGIN_BASE_PATH", $plugin_path);

add_action('plugins_loaded', 'wc_mistertango_pay_gateway', 0);

/*
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set("log_errors", 1);
ini_set("error_log", ABSPATH."wp-content/plugins/".plugin_basename(dirname(__FILE__))."/php_error_log.txt");
*/
//load_plugin_textdomain('mistertango-woocommerce', false, dirname( plugin_basename( __FILE__ ) ) . '/language');

function logUsingMethods($sTitle)
{
    $fp = fopen(ABSPATH."wp-content/plugins/".plugin_basename(dirname(__FILE__))."/used_functions.txt", 'a+');
    fwrite($fp, $sTitle . "\n");
    fclose($fp);
}

function wc_mistertango_pay_gateway()
{
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }

    add_filter('woocommerce_payment_gateways', 'wc_mistertango_gateway');
    load_plugin_textdomain('mistertango-woocommerce', false, dirname( plugin_basename( __FILE__ ) ) . '/language');

    function wc_mistertango_gateway($methods)
    {
        $methods[] = 'WC_Mistertango';
        return $methods;
    }

    class WC_Mistertango extends WC_Payment_Gateway
    {
        protected $msg = array();

        public function __construct()
        {
            global $woocommerce;

            $this->id = 'mistertango';

            if (is_admin()) {
                $this->order_button_text = __('Checkout ', 'mistertango-woocommerce');
            } else {
                $this->order_button_text = __('Checkout ', 'mistertango-woocommerce') . get_woocommerce_currency_symbol() . " " . $this->get_order_total();
            }

            $this->method_title = __('Mister Tango Payment', 'mistertango-woocommerce');
            //$this->icon         = WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/images/mister_tango_en.png';
            $this->icon         = '';
            $this->has_fields   = false;
            $this->liveurl      = "";
            $this->init_form_fields();

            // Load the settings.
            $this->init_settings();
            $this->installDb();

            /*** Define user set variables  ***/
            $this->enabled              = $this->settings['enabled'];
            $this->title                = $this->settings['title'];
            $this->description          = $this->settings['description'];
            $this->username             = $this->settings['username'];
            $this->secret_key           = $this->settings['secret_key'];
            $this->mistertango_language = $this->settings['mistertango_language'];
            $this->msg['message']       = "";
            $this->msg['class']         = "";

            // Actions
            add_action('wp_enqueue_scripts', array($this, 'payment_scripts'), 999999);
            add_action('woocommerce_api_wc_mistertango', array($this, 'check_mistertango_response'));
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            add_action('woocommerce_thankyou_mistertango', array($this, 'thankyou'));
            add_action('woocommerce_review_order_before_payment', array($this, 'generate_mistertango_before_form'));
            add_action('before_woocommerce_pay', array($this, 'before_woocommerce_pay_form'));
        }

        public function generate_mistertango_before_form()
        {
            echo "<div id='mistertango_ajax_form' style='display:none'>
			          <input type='hidden' id='mrTangoOrderId' name='mrTangoOrderId' value='' />
			      </div>";
        }

        public function before_woocommerce_pay_form()
        {
            global $wpdb;

            /////////////
            $websocket_id       = '';
            $db_transaction_id  = '';

            $order_id           = absint(get_query_var('order-pay'));


            /** initailize all the variable ***/
            $order = new WC_Order($order_id);

            $payment_response_url = WC()->api_request_url('WC_Mistertango');

            $redirect_url                   = add_query_arg('wooorderid', $order_id, $payment_response_url);
            $mister_tango_url_information   = add_query_arg('mistertangoprocess', "information", add_query_arg('wooorderid', $order_id, $payment_response_url));
            $mister_tango_url_confirm       = add_query_arg('mistertangoprocess', "confirm", add_query_arg('wooorderid', $order_id, $payment_response_url));
            $mister_tango_url_histories     = add_query_arg('mistertangoprocess', "histories", add_query_arg('wooorderid', $order_id, $payment_response_url));
            $cancel_url                     = add_query_arg('wooorderid', $order_id, add_query_arg('wc-api', 'WC_Mistertango', $this->get_return_url($order)));

            $username   = $this->username;
            $secret_key = $this->secret_key;

//            $countries  = new WC_Countries;

            $language       = $this->mistertango_language;
            $currency       = get_woocommerce_currency();
            $amount         = $order->get_total();
            $billing_email  = $order->billing_email;
            $button_confirm = __('Click Here ', 'mistertango-woocommerce');

//            unset($countries);

            $db_prefix              = $wpdb->prefix;
            $order_pending_status   = "pending";
            $order_status_id        = $order->has_status($order_pending_status);
            $websocket_query        = $wpdb->get_row("SELECT * FROM " . $db_prefix . "transactions_mistertango WHERE `order` ='" . esc_sql($order_id) . "'");

            if (is_object($websocket_query)) {
                $websocket_id       = $websocket_query->websocket;
                $db_transaction_id  = $websocket_query->transaction;
            }

            $status = $order->get_status();

            if ($status == 'processing') {
                $redirect_url = $this->get_return_url($order);
                // $order_info->get_view_order_url();
                //$redirect_url =$order->get_view_order_url();
                wp_redirect($redirect_url);
                exit(0);
            }

            $button_html = '';
            if ((isset($websocket_id)) && (isset($db_transaction_id)) && ($websocket_id != '') && ($db_transaction_id != '')) {
                if ($order_status_id == 1) {
                    //////////////
                    $button_html = '<a href=""
											class="mistertango-button-pay"
											data-ws-id="' . $websocket_id . '"
											data-language="' . $language . '"
											data-customer="' . $billing_email . '"
											data-order="' . $order_id . '"
											data-amount="' . $amount . '"
											data-currency="' . $currency . '"
											data-transaction="' . $db_transaction_id . '">
											' . $button_confirm . '
					</a>';
                }
            } else {
                $time = time();
                $transaction_id = $order_id . '_' . $time;
                $button_html = '	<a href="#"
					id="button-confirm" class="btn btn-primary mistertango-button-pay"
					data-language="' . $language . '"
					data-customer="' . $billing_email . '"
					data-amount="' . $amount . '"
					data-currency="' . $currency . '"
					data-transaction="' . $transaction_id . '">
					' . $button_confirm . '	</a>';
            }

            if ($order->payment_method == $this->id || $websocket_id != '') {
                echo "<style>
						#payment {
							display: none;
						}
						</style>";
                echo "<div id='mistertango_ajax_after_form' style='display:none'>
					<input type='hidden' id='mrTangoOrderId' name='mrTangoOrderId' value='" . $order_id . "' />";;
                $order_date = $order->order_date;
                echo '<h1 class="page-heading" style="text-transform:uppercase;">'.__('Payment information', 'mistertango-woocommerce').'</h1>
					<table id=mistertango-information-order-states" style="margin-bottom:30px;">
						<thead>
							<tr style="border:1px solid #d6d4d4;">
								<th width="20%" style="padding:3px;border:1px solid #d6d4d4;background:#fbfbfb;border-bottom-width: 1px;color: #333;vertical-align: middle;">'.__('Date', 'mistertango-woocommerce').'</th>
								<th style="padding:3px;border:1px solid #d6d4d4;background:#fbfbfb;border-bottom-width: 1px;color: #333;vertical-align: middle;" width="80%">'.__('Status', 'mistertango-woocommerce').'</th>
							</tr>
						</thead>
						<tbody>
							<tr class="first_item last_item item">
								<td style="vertical-align:middle;padding:3px;border:1px solid #d6d4d4;">' . $order_date . '</td>
								<td style="padding:10px;border:1px solid #d6d4d4;">
									<p class="jsAllowDifferentPayment">
									    ' . __('Check your email, we sent you an invoice. If you wish to use other methods for payment - ', 'mistertango-woocommerce') .'
									    ' . $button_html . '
									</p>
								</td>
							</tr>
						</tbody>
					</table>';
            } else {
                echo "<div id='mistertango_ajax_after_form' style='display:none'>
						<input type='hidden' id='mrTangoOrderId' name='mrTangoOrderId' value='" . $order_id . "' />" . $button_html . "</div>";
            }


        }

        public function admin_options()
        {
            global $woocommerce;

            ?>
                <h3><?php _e('Mistertango Payment Gateway', 'mistertango-woocommerce'); ?></h3>

                <table class="form-table">
                    <?php $this->generate_settings_html(); ?>
                </table>
            <?php
        } // End admin_options()

        function init_form_fields()
        {
            global $woocommerce;

            $this->form_fields  = array( 'enabled' => array('title'         => __('Enable/Disable', 'mistertango-woocommerce'),
                                                            'type'          => 'checkbox',
                                                            'label'         => __('Enable Mister Tango Payment', 'mistertango-woocommerce'), 'default' => 'no'),
                                                            'title'         => array(   'title'         => __('Title', 'mistertango-woocommerce'),
                                                                                        'type'          => 'text',
                                                                                        'desc_tip'      => true,
                                                                                        'placeholder'   => __('Title', 'mistertango-woocommerce'),
                                                                                        'description'   => __('Payment\'s title name.', 'mistertango-woocommerce'),
                                                                                        'default'       => __('Bank Transfer, Credit card and other', 'mistertango-woocommerce')
                                                                                ),
                                                            'description'   => array(   'title'         => __('Description:', 'mistertango-woocommerce'),
                                                                                        'type'          => 'textarea',
                                                                                        'desc_tip'      => true,
                                                                                        'placeholder'   => __('Description', 'mistertango-woocommerce'),
                                                                                        'description'   => __('Payment\'s description', 'mistertango-woocommerce'),
                                                                                        'default'       => __('Payments collected by Mistertango', 'mistertango-woocommerce')
                                                                                ),
                                                            'username'      => array(   'title'         => __('Username', 'mistertango-woocommerce'),
                                                                                        'type'          => 'text',
                                                                                        'desc_tip'      => true,
                                                                                        'placeholder'   => __('Username', 'mistertango-woocommerce'),
                                                                                        'description'   => __('Username of Mistertango', 'mistertango-woocommerce')
                                                                                ),
                                                            'secret_key'    => array(   'title'         => __('Secret Key', 'mistertango-woocommerce'),
                                                                                        'type'          => 'text',
                                                                                        'desc_tip'      => true,
                                                                                        'placeholder'   => __('Secret Key', 'mistertango-woocommerce'),
                                                                                        'description'   => __('Secret Key Given to Merchant by Mistertango', 'mistertango-woocommerce')
                                                                                ),
                                                            'mistertango_language'  => array(   'title'         => __('Language', 'mistertango-woocommerce'),
                                                                                                'description'   => __('Payment Window Language', 'mistertango-woocommerce'),
                                                                                                'desc_tip'      => true,
                                                                                                'type'          => 'select',
                                                                                                'options'       => array(   'en' => 'English',
                                                                                                                            'lt' => 'Lietuvių',
                                                                                                                            'et' => 'Estonian',
                                                                                                                            'lv' => 'Latvian'
                                                                                                                    )
                                                                                        )
                                        );
        }

        function installDb()
        {
            global $wpdb;

            $db_prefix = $wpdb->prefix;

            $wpdb->query("CREATE TABLE IF NOT EXISTS `" . $db_prefix . "transactions_mistertango` (
						    `transaction` varchar(255) NOT NULL,
						    `amount` DECIMAL(10,2) NOT NULL,
						    `order` int(10) NOT NULL,
						    `websocket` varchar(255) NULL,
						    `payment_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
						    PRIMARY KEY (`transaction`)
						) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

            $wpdb->query("CREATE TABLE IF NOT EXISTS `" . $db_prefix . "callbacks_mistertango` (
						    `callback` VARCHAR(255) NOT NULL,
						    `transaction` VARCHAR(255) NOT NULL,
						    `amount` DECIMAL(10,2) NOT NULL,
						    `callback_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
						    PRIMARY KEY (`callback`)
                         );");
        }

        function uninstallDb()
        {
            global $wpdb;

            $db_prefix = $wpdb->prefix;
            $wpdb->query("DROP TABLE IF EXISTS `" . $db_prefix . "transactions_mistertango`");
            $wpdb->query("DROP TABLE IF EXISTS `" . $db_prefix . "callbacks_mistertango`");
        }

        function payment_fields()
        {
            if ($this->description)
                echo wpautop(wptexturize($this->description));
        }

        public function payment_scripts($order_id)
        {
            if (!is_checkout() || !$this->is_available()) {
                return;
            }

            $order_id   = absint(get_query_var('order-pay'));
            $username   = $this->username;
            $time       = time();

            $payment_response_url           = WC()->api_request_url('WC_Mistertango');
            $mister_tango_process_check_url = add_query_arg('wooorderid', $order_id, add_query_arg('mistertangoprocess', "checkstatus", $payment_response_url));
            $mister_tango_url_confirm       = add_query_arg('mistertangoprocess', "confirm", $payment_response_url);
            $mister_tango_url_information   = add_query_arg('mistertangoprocess', "information", $payment_response_url);
            $payment_response_offline_url   = add_query_arg('OfflinePayment', "yes", $payment_response_url);;
            $mister_tangoajax_submit_url    = WC()->cart->get_checkout_url();;

            wp_localize_script('jquery', 'mrTangoUrlScript', urlencode("https://mistertango.com/resources/scripts/mt.collect.js?v=" . $time));
            wp_localize_script('jquery', 'mrTangoUsername', $username);
            wp_localize_script('jquery', 'mrTangoUrlCallbackOffline', urlencode($payment_response_offline_url));
            wp_localize_script('jquery', 'mrTangoUrlConfirm', urlencode($mister_tango_url_confirm));
            wp_localize_script('jquery', 'mrTangoUrlInformation', urlencode($mister_tango_url_information));
            wp_localize_script('jquery', 'mrTangoUrlProcessCheck', urlencode($mister_tango_process_check_url));

            wp_enqueue_script('wc-mistertango-jquery', WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . "/" . 'js/' . 'mister_tango_ajax.js', array('jquery'), '1.0', true);
            wp_enqueue_script('wc-mistertango', WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . "/" . 'js/' . 'mister_tango.js', array('wc-mistertango-jquery'), '', true);

            wp_localize_script('jquery', 'mister_tangoajax_submit_url', urlencode($mister_tangoajax_submit_url));

            if ($order_id != '') {
                wp_localize_script('jquery', 'mrTangoOrderId', urlencode($order_id));
            } else {
                $order_id = null;
                wp_localize_script('jquery', 'mrTangoOrderId', $order_id);
            }

        }


        /**
         * Check for valid Mistertango server callback
         **/
        function check_mistertango_response()
        {
            global $woocommerce;
            $wooorderid = 0;
            $mistertangoprocess = '';


            $wc_api         = (isset($_REQUEST['wc-api'])       ? $_REQUEST['wc-api']       : '');
            $transaction    = (isset($_REQUEST['transaction'])  ? $_REQUEST['transaction']  : '');
            $websocket      = (isset($_REQUEST['websocket'])    ? $_REQUEST['websocket']    : '');
            $amount         = (isset($_REQUEST['amount'])       ? $_REQUEST['amount']       : 0);
            $order_id       = (isset($_REQUEST['wooorderid'])   ? $_REQUEST['wooorderid']   : '');
            $currency       = (isset($_REQUEST['currency'])     ? $_REQUEST['currency']     : '');

            if ($order_id == '') {
                $order_id = (isset($_REQUEST['order']) ? $_REQUEST['order'] : '');
            }

            if ($order_id == 'undefined')
                $order_id = '';

            if ($order_id == '') {
                $details = '';

                if (isset($_REQUEST['details']))
                    $details = $_REQUEST['details'];

                $details_array = explode("_", $details);

                if (count($details_array) == 2 && isset($details_array[0])) {
                    $order_id = $details_array[0];
                }
            }

            if ($order_id == '' && $transaction != '') {

                $transaction_id = explode('_', $transaction);

                if (count($transaction_id) == 2) {
                    if (isset($transaction_id[0]))
                        $order_id = $transaction_id[0];
                }
            }

            if (isset($_REQUEST['mistertangoprocess']))
                $mistertangoprocess = $_REQUEST['mistertangoprocess'];

            if ($mistertangoprocess == "checkstatus") {
                @ob_clean();
                header("Content-Type: application/json");

                $order_info = new WC_Order($order_id);
                $return_data = array('success' => false, 'msg' => 'pending');
                $status = $order_info->get_status();

                if ($status == 'processing') {
                    $return_data = array('success' => true, 'msg' => 'processing');
                }

                echo json_encode($return_data);
                exit(0);
            } elseif ($mistertangoprocess == "confirm") {

                $this->misterTangoConfirm($transaction, $websocket, $amount, $order_id);
                return true;
            } elseif ($mistertangoprocess == "information") {

                $this->misterTangoInformation($transaction, $websocket, $amount, $order_id);
                return true;
            }

            $hash = isset($_REQUEST['hash']) ? $_REQUEST['hash'] : false;
            $order_info = new WC_Order($order_id);
            $order_amount = $order_info->get_total();

            if ($hash !== false) {

                $data = json_decode($this->decrypt($hash, $this->secret_key));

                //Added by T.K.: If decryption fail - die!
                if (empty($data))
                    die();

                $data->custom = isset($data->custom) ? json_decode($data->custom) : null;
                $hash_data = $data;
                $callback_uuid = '';

                if (isset($data->callback_uuid))
                    $callback_uuid = $data->callback_uuid;

                $data_custom_payment_type   = $data->custom->type;            //MISTERTANGO,BITCOIN,BANK_LINK,CREDIT_CARD,BANK_TRANSFER
                $data_custom_invoice        = $data->custom->invoice;        //6963898d-7a38-11e5-aab7-0203788e2242
                $data_custom_status         = $data->custom->status;        //PAID , OFFLINE ,OPENED ,CLOSED
                $data_custom_email          = $data->custom->contact->email;//Payer Email address
                $data_status                = $data->status;                //ACCEPTED

                if (isset($data->custom) && isset($data->custom->description)) {
                    $transaction = explode('_', $data->custom->description);

                    if (count($transaction) == 2) {
                        if ($this->isNotDuplicateCallback($callback_uuid) && $callback_uuid != '') {
                            $this->addCallback($data);
                            try {
                                $transaction_id = implode('_', $transaction);
                                $order_total_amount = $order_info->get_total();
                                $this->closeOrder($transaction_id, $data->custom->data->amount, $hash_data);
                                die('OK');
                            } catch (Exception $e) {
                                die();
                            }
                        }
                    }
                }
            }
            die();
        }

        function write_debug_log($log_text)
        {
            $log_file = ABSPATH . "wp-content/plugins/" . plugin_basename(dirname(__FILE__)) . "/response.txt";
            $handle = fopen($log_file, "a");
            $log_separater_request_row = "\n";//"\n===============start===============\n";
            $log_separater_end_row = "";//"\n===============end===============\n";
            fwrite($handle, $log_separater_request_row);
            if (is_object($log_text)) {
                $log_text = (array)$log_text;
                foreach ($log_text as $log_request_details) {
                    if (is_object($log_request_details)) {
                        fwrite($handle, "inside is _object");
                        $temp = serialize($log_request_details);
                        fwrite($handle, $temp);
                        fwrite($handle, "\n");
                    } else {
                        fwrite($handle, $log_request_details);
                        fwrite($handle, "\n");
                    }
                }
                fclose($handle);
                return true;
            }
            if (is_array($log_text) == false) {
                fwrite($handle, $log_text);
                fwrite($handle, "\n");
                fwrite($handle, $log_separater_end_row);
                fclose($handle);
                return true;
            }
            foreach ($log_text as $key => $log_request_details) {
                fwrite($handle, $key . "=>" . $log_request_details);
                fwrite($handle, "\n");
            }
            fwrite($handle, $log_separater_end_row);
            fclose($handle);
            return true;
        }

        function misterTangoConfirm($transaction, $websocket, $amount, $order_id)
        {
            @ob_clean();
            header("Content-Type: application/json");
            if ($transaction == '' || $websocket == '' || $amount == '') {
                $return_data = array('success' => false, 'error' => 'Invalid parameters');
                echo wp_json_encode($return_data);
                die();
            }

            $order_info = '';
            if (isset($order_id)) {
                $order_info = new WC_Order($order_id);
            }

            if ($this->get_title() == $this->title) {
                $this->openOrder($transaction, $amount, $websocket);
                $order_info->add_order_note('payment websocket: ' . $websocket);
                $order_info->add_order_note('payment transaction ' . $transaction);
                $order_info->add_order_note("amount=" . $amount);
                $return_data = array('success' => true, 'order' => $order_id);
                echo wp_json_encode($return_data);
                die();
            }

            $return_data = array('success' => false, 'error' => 'Invalid transaction');
            echo wp_json_encode($return_data);
            die();
        }

        function misterTangoInformation($transaction, $websocket, $amount, $order_id)
        {
            global $woocommerce;
            $payment_response_url = WC()->api_request_url('WC_Mistertango');

            if (empty($order_id)) {
                $return_data = array('success' => false, 'error' => 'Invalid parameters');
                echo wp_json_encode($return_data);
                die();
            }

            $order_info = '';

            if (isset($order_id)) {
                $order_info = new WC_Order($order_id);
            }

            $mister_tango_username = $this->username;

            $mister_tango_url_information   = add_query_arg('mistertangoprocess', "information", add_query_arg('wooorderid', $order_id, $payment_response_url));
            $mister_tango_url_confirm       = add_query_arg('mistertangoprocess', "confirm", add_query_arg('wooorderid', $order_id, $payment_response_url));
            $mister_tango_url_histories     = add_query_arg('mistertangoprocess', "histories", add_query_arg('wooorderid', $order_id, $payment_response_url));

            $button_continue        = __('Continue', 'mistertango-woocommerce');
            $button_reorder_button  = __('Checkout ', 'mistertango-woocommerce') . get_woocommerce_currency_symbol() . " " . $this->get_order_total();;

            $order_pending_status = "pending";
            get_header();

            echo '<div id="primary" class="content-area">';
            echo '<main id="main" class="site-main" role="main">
				<article  class="page type-page status-publish hentry" style = "padding: 7.6923%;">
				<h1>' . __('Mistertango Payment Information', 'mistertango-woocommerce') . '</h1>';

            $order_status = $order_info->has_status($order_pending_status);
            //$this->misterTangoHistories($order_id, array(), false);
            $status = $order_info->get_status();

            if ($order_info->payment_method != $this->id) {
                $order_info->set_payment_method($this);
            }

            $msg['message'] = __("Thank you for shopping with us. Your Mister Tango Payment Transaction is successful . We will be shipping your order to you soon.", 'mistertango-woocommerce');
            $msg['class'] = 'success';

            if ($status == 'processing' || $status == 'on-hold') {
                $redirect_url = $this->get_return_url($order_info);// $order_info->get_view_order_url();
            } else {
                $redirect_url = $order_info->get_checkout_payment_url();
            }

            wp_redirect($redirect_url);
            exit(0);
            return true;
        }

        function decrypt($encoded_text, $key)
        {
            $key = str_pad($key, 32, "\0");
            
            $encoded_text = trim($encoded_text);
            $ciphertext_dec = base64_decode($encoded_text);
            $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
            $iv_dec = substr($ciphertext_dec, 0, $iv_size);
            $ciphertext_dec = substr($ciphertext_dec, $iv_size);
            $sResult = @mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $ciphertext_dec, MCRYPT_MODE_CBC, $iv_dec);
            return trim($sResult);
        }

        function isNotDuplicateCallback($callback)
        {
            global $woocommerce, $wpdb;
            $db_prefix = $wpdb->prefix;
            $has_duplicate = $wpdb->query(" SELECT 1
                                            FROM `" . $db_prefix . "callbacks_mistertango`
                                            WHERE `callback` ='" . esc_sql($callback) . "'");

            $has_duplicate = is_array($has_duplicate) ? reset($has_duplicate) : null;
            return $has_duplicate ? false : true;
        }

        function addCallback($data)
        {
            global $woocommerce, $wpdb;

            $db_prefix = $wpdb->prefix;
            $wpdb->query("INSERT INTO `" . $db_prefix . "callbacks_mistertango`
				(
					`callback`,
					`transaction`,
					`amount`
				)
				VALUES
				(
					'" . esc_sql($data->callback_uuid) . "',
					'" . esc_sql($data->custom->description) . "',
					'" . esc_sql($data->custom->data->amount) . "'
				)");
        }

        function closeOrder($transaction_id, $amount, $hash_data)
        {
            $order_id = '';
            global $woocommerce, $wpdb;
            $db_prefix = $wpdb->prefix;
            $order_row = $wpdb->get_row($wpdb->prepare("SELECT `order`
										                FROM `" . $db_prefix . "transactions_mistertango`
										                WHERE `transaction` = %s", $transaction_id));

            if (is_object($order_row)) {
                $order_id = $order_row->order;
            }

            if (empty($order_id)) {
                $this->openOrder($transaction_id, $amount);
                $order_row = $wpdb->get_row($wpdb->prepare('SELECT `order`
								                            FROM `' . $db_prefix . 'transactions_mistertango`
                                                            WHERE `transaction` =  %s', $transaction_id));

                if (is_object($order_row)) {
                    $order_id = $order_row->order;
                }
            }

            $order_info         = new WC_Order($order_id);
            $order_status       = "processing";
            $order_total_amount = $order_info->get_total();
            $payment_type       = $hash_data->custom->type;            //MISTERTANGO,BITCOIN,BANK_LINK,CREDIT_CARD,BANK_TRANSFER
            $invoice            = $hash_data->custom->invoice;        //6963898d-7a38-11e5-aab7-0203788e2242
            $payment_status     = $hash_data->custom->status;        //PAID , OFFLINE ,OPENED ,CLOSED
            $payer_email        = $hash_data->custom->contact->email;//Payer Email address
            $data_serialize     = serialize($hash_data);

            if ($payment_type != '')
                $order_info->add_order_note("payment_type " . $payment_type);

            if ($invoice != '')
                $order_info->add_order_note("Invoice " . $invoice);

            if ($payment_status != '')
                $order_info->add_order_note("payment_status " . $payment_status);

            if ($payer_email != '')
                $order_info->add_order_note("Payer Email " . $payer_email);

            if ($data_serialize != '')
                $order_info->add_order_note("data_serialize " . $data_serialize);

            $order_info->add_order_note("transaction_id=" . $transaction_id);
            $order_info->add_order_note("amount=" . $amount);

            if (bcdiv($order_total_amount, 1, 2) != bcdiv($amount, 1, 2)) {
                $order_status = "pending";
                $transauthorised = false;

                if ($payment_status == "paid") {
                    $order_status = "on-hold";
                    $transauthorised = false;
                }
            }

            if ($order_status == "processing") {
                $comment = __('Amount received', 'mistertango-woocommerce') . ': ' . $amount;
                $transauthorised = true;
                $msg['message'] = __("Thank you for shopping with us. Your MisterTango Payment Transaction is successful . We will be shipping your order to you soon.", 'mistertango-woocommerce');
                $msg['class'] = 'success';
                $order_info->update_status($order_status);
                $order_info->payment_complete();
                $order_info->add_order_note($comment);
                $order_info->add_order_note($msg['message']);
                $woocommerce->cart->empty_cart();
            } elseif ($order_status == "on-hold") {
                $order_info->update_status($order_status);
            }

            if ($transauthorised == false) {
                $msg['class'] = __('error', 'mistertango-woocommerce');
                $msg['message'] = '<strong>' . __('Transaction not completed', 'mistertango-woocommerce') . '</strong>';
                $order_info->update_status($order_status);
                $order_info->add_order_note("payment_status is " . $payment_status);
                $order_info->add_order_note($msg['message']);
            }

            return true;
        }

        function openOrder($transaction_id, $amount, $websocket_id = null)
        {
            global $woocommerce;

            $transaction = explode('_', $transaction_id);

            if (count($transaction) == 2) {
                $order_id = $transaction[0];
                $order_info = new WC_Order($order_id);
                $msg['class'] = "success";
                
                //T.K.: New order already has pending status.
                //$order_info->update_status('pending');
                $this->addTransaction($transaction_id, $websocket_id, $order_id, $amount);
                return true;
            }

            return false;
        }

        function addTransaction($transaction, $websocket, $order, $amount)
        {
            global $wpdb;
            $db_prefix = $wpdb->prefix;
            $websocket_query = $wpdb->get_row("SELECT * FROM " . $db_prefix . "transactions_mistertango WHERE `order` ='" . esc_sql($transaction) . "'");

            if (is_object($websocket_query)) {
                $websocket_id = $websocket_query->websocket;
                if ($websocket_id == $websocket)
                    return true;
            }

            $wpdb->query("INSERT INTO " . $db_prefix . "transactions_mistertango
				(
					`transaction`,
					`websocket`,
					`order`,
					`amount`
				)
				VALUES
				(
					'" . esc_sql($transaction) . "',
					'" . esc_sql($websocket) . "',
					'" . esc_sql($order) . "',
					'" . esc_sql($amount) . "'
				)");
        }

        /**
         * Process the payment and return the result
         **/
        function process_payment($order_id)
        {
            global $woocommerce;
            $order = new WC_Order($order_id);
            $order_id_ajax = '';
            $result = "failure";
            $button_html = '';

            if (isset($order->id)) {
                $order_id_ajax = $order->id;
                $result = "success";
                $language = $this->mistertango_language;
                $currency = get_woocommerce_currency();
                $amount = $order->get_total();
                $billing_email = $order->billing_email;
                $time = time();
                $transaction_id = $order_id_ajax . '_' . $time;
                $button_confirm = __('Checkout ', 'mistertango-woocommerce') . get_woocommerce_currency_symbol() . " " . $this->get_order_total();
                $button_html = '	<button type="button"
					id="button-confirm" class="btn btn-primary mistertango-button-pay"
					data-language="' . $language . '"
					data-customer="' . $billing_email . '"
					data-amount="' . $amount . '"
					data-currency="' . $currency . '"
					data-transaction="' . $transaction_id . '">
					' . $button_confirm . '	</button>';
            }

            $return_data = array('result' => $result, 'order_id' => $order_id_ajax, 'button_html' => $button_html);
            echo wp_json_encode($return_data);
            die();
            // Return payment page
            return array('result' => 'success', 'redirect' => add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, get_permalink(wc_get_page_id('pay')))));
        }

        /**
         * thankyou_page
         **/
        function thankyou($order)
        {
            echo '<p>' . __('', 'mistertango-woocommerce') . '</p>';
        }

    }
}
