<?php
/**
 * Plugin Name: Nash Link Checkout for WooCommerce
 * Plugin URI: https://link.nash.io
 * Description: Bitcoin and cryptocurrency payments powered by Nash
 * Version: 1.1.0
 * Author: Nash
 * Author URI: mailto:contact@nash.io?subject=payments Checkout for WooCommerce
 */

require_once 'lib/NashLinkApi.php';

use Nash\Link\NashLinkApi;

if (!defined('ABSPATH')): exit; endif;

global $current_user;

function NPC_Logger($msg, $type = null, $isJson = false, $error = false)
{
    $nashlink_checkout_options = get_option('woocommerce_nashlink_checkout_gateway_settings');
    $structure = plugin_dir_path(__FILE__) . 'logs/';
    if (!file_exists($structure)) {
        mkdir($structure);
    }
    $transaction_log = plugin_dir_path(__FILE__) . 'logs/' . date('Ymd') . '_transactions.log';
    $error_log = plugin_dir_path(__FILE__) . 'logs/' . date('Ymd') . '_error.log';

    $header = PHP_EOL . '======================' . $type . '===========================' . PHP_EOL;
    $footer = PHP_EOL . '=================================================' . PHP_EOL;

    if ($error):
        error_log($header, 3, $error_log);
        error_log($msg, 3, $error_log);
        error_log($footer, 3, $error_log);
    else:
        if ($nashlink_checkout_options['nashlink_log_mode'] == 1):
            error_log($header, 3, $transaction_log);
            if ($isJson):
                error_log(print_r($msg, true), 3, $transaction_log);
            else:
                error_log($msg, 3, $transaction_log);
            endif;
            error_log($footer, 3, $transaction_log);
        endif;
    endif;
}

function NPC_getEnvData()
{
    $nashlink_checkout_options = get_option('woocommerce_nashlink_checkout_gateway_settings');
    $environment = $nashlink_checkout_options['nashlink_checkout_environment'];
    $api_key = $nashlink_checkout_options['nashlink_checkout_api_key_sandbox'];
    $api_secret_key = $nashlink_checkout_options['nashlink_checkout_api_secret_key_sandbox'];
    if ($environment == 'prod') {
        $api_key = $nashlink_checkout_options['nashlink_checkout_api_key_prod'];
        $api_secret_key = $nashlink_checkout_options['nashlink_checkout_api_secret_key_prod'];
    }
    return array('environment' => $environment, 'api_key' => $api_key, 'api_secret_key' => $api_secret_key);
}

function nashlink_checkout_woocommerce_nashlink_failed_requirements()
{
    global $wp_version;
    global $woocommerce;
    $errors = array();

    // WooCommerce required
    if (true === empty($woocommerce)) {
        $errors[] = 'The WooCommerce plugin for WordPress needs to be installed and activated. Please contact your web server administrator for assistance.';
    } elseif (true === version_compare($woocommerce->version, '2.2', '<')) {
        $errors[] = 'Your WooCommerce version is too old. The NashLink payment plugin requires WooCommerce 2.2 or higher to function. Your version is ' . $woocommerce->version . '. Please contact your web server administrator for assistance.';
    } elseif (!function_exists('curl_version')) {
        $errors[] = 'cUrl needs to be installed/enabled for NashLink Checkout to function';
    }
    if (empty($errors)):
        return false;
    else:
        return implode("<br>\n", $errors);
    endif;
}

add_action('plugins_loaded', 'wc_nashlink_checkout_gateway_init', 11);
#create the table if it doesnt exist

function nashlink_checkout_plugin_setup()
{
    
    $failed = nashlink_checkout_woocommerce_nashlink_failed_requirements();
    $plugins_url = admin_url('plugins.php');

    if ($failed === false) {

        global $wpdb;
        $table_name = '_nashlink_checkout_transactions';

        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS $table_name(
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `order_id` varchar(255) NOT NULL,
        `transaction_id` varchar(255) NOT NULL,
        `transaction_status` varchar(50) NOT NULL DEFAULT 'new',
        `date_added` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        #check out of date plugins
        $plugins = get_plugins();
        foreach ($plugins as $file => $plugin) {
            if ('NashLink Woocommerce' === $plugin['Name'] && true === is_plugin_active($file)) {
                deactivate_plugins(plugin_basename(__FILE__));
                wp_die('NashLink for WooCommerce requires that the old plugin, <b>NashLink Woocommerce</b>, is deactivated and deleted.<br><a href="' . $plugins_url . '">Return to plugins screen</a>');
            }
        }

    } else {

        // Requirements not met, return an error message
        wp_die($failed . '<br><a href="' . $plugins_url . '">Return to plugins screen</a>');

    }

}
register_activation_hook(__FILE__, 'nashlink_checkout_plugin_setup');

function nashlink_checkout_insert_order_note($order_id = null, $transaction_id = null)
{
    global $wpdb;

    if ($order_id != null && $transaction_id != null):
        global $woocommerce;

    //Retrieve the order
    $order = new WC_Order($order_id);
    $order->set_transaction_id($transaction_id);
    $order->save();
    //Retrieve the transaction ID

        $table_name = '_nashlink_checkout_transactions';
        $wpdb->insert(
            $table_name,
            array(
                'order_id' => $order_id,
                'transaction_id' => $transaction_id,
            )
        );
    else:
        NPC_Logger('Missing values' . PHP_EOL . 'order id: ' . $order_id . PHP_EOL . 'transaction id: ' . $transaction_id, 'error', false, true);
    endif;

}

function nashlink_checkout_update_order_note($order_id = null, $transaction_id = null, $transaction_status = null)
{
    global $wpdb;
    $table_name = '_nashlink_checkout_transactions';
    if ($order_id != null && $transaction_id != null && $transaction_status != null):
        $wpdb->update($table_name, array('transaction_status' => $transaction_status), array("order_id" => $order_id, 'transaction_id' => $transaction_id));
    else:
        NPC_Logger('Missing values' . PHP_EOL . 'order id: ' . $order_id . PHP_EOL . 'transaction id: ' . $transaction_id . PHP_EOL . 'transaction status: ' . $transaction_status . PHP_EOL, 'error', false, true);
    endif;
}

function nashlink_checkout_get_order_transaction($order_id, $transaction_id)
{
    global $wpdb;
    $table_name = '_nashlink_checkout_transactions';
    $rowcount = $wpdb->get_var($wpdb->prepare("SELECT COUNT(order_id) FROM $table_name WHERE transaction_id = %s",$transaction_id));
    return $rowcount;

}
function nashlink_checkout_get_order_id_nashlink_invoice_id($transaction_id)
{
    global $wpdb;
    $table_name = '_nashlink_checkout_transactions';
    $order_id = $wpdb->get_var($wpdb->prepare("SELECT order_id FROM $table_name WHERE transaction_id = %s LIMIT 1", $transaction_id));
    return $order_id;
}
function nashlink_checkout_delete_order_transaction($order_id)
{
    global $wpdb;
    $table_name = '_nashlink_checkout_transactions';
    $wpdb->query($wpdb->prepare("DELETE FROM $table_name WHERE order_id = %s",$order_id));

}

function wc_nashlink_checkout_gateway_init()
{
    if (class_exists('WC_Payment_Gateway')) {
        class WC_Gateway_NashLink extends WC_Payment_Gateway
        {

            public function __construct()
            {
                $nashlink_checkout_options = get_option('woocommerce_nashlink_checkout_gateway_settings');

                $this->id = 'nashlink_checkout_gateway';

                $this->has_fields = true;
                $this->method_title = __(NPC_getNashLinkVersionInfo($clean = true), 'wc-nashlink');
                $this->method_label = __('Nash Link', 'wc-nashlink');
                $this->method_description = __('Bitcoin and cryptocurrency payments powered by Nash', 'wc-nashlink');

                if (empty($_GET['woo-nashlink-return'])) {
                    $this->order_button_text = __('Pay with Nash Link', 'woocommerce-gateway-nashlink_checkout_gateway');
                }
                // Load the settings.
                $this->init_form_fields();
                $this->init_settings();

                // Define user set variables
                $this->title = 'Nash Link';
                $this->description = $this->get_option('description') . '<br>';
                $this->instructions = $this->get_option('instructions', $this->description);

                // Actions
                add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

                // Customer Emails
                add_action('woocommerce_email_before_order_table', array($this, 'email_instructions'), 10, 3);
            }

            public function email_instructions($order, $sent_to_admin, $plain_text = false)
            {
                if ($this->instructions && !$sent_to_admin && 'nashlink_checkout_gateway' === $order->get_payment_method() && $order->has_status('processing')) {
                    echo wp_kses_post(wpautop(wptexturize($this->instructions)) . PHP_EOL);
                }
            }
            
            public function init_form_fields()
            {
                $wc_statuses_arr = wc_get_order_statuses();
                unset($wc_statuses_arr['wc-cancelled']);
                unset($wc_statuses_arr['wc-refunded']);
                unset($wc_statuses_arr['wc-failed']);
                #add an ignore option
                $wc_statuses_arr['nashlink-ignore'] = "Do not change status";
                
                $this->form_fields = array(
                    'enabled' => array(
                        'title' => __('Enable/Disable', 'woocommerce'),
                        'label' => __('Enable Nash Link', 'woocommerce'),
                        'type' => 'checkbox',
                        'description' => '',
                        'default' => 'no',
                    ),
                    'nashlink_checkout_info' => array(
                        'description' => __('You should not ship any products until Nash Link has finalized your transaction.<br>The order will stay in a <b>Pending payment</b> or <b>Processing</b> state, and will automatically change to <b>Completed</b> after the payment has been confirmed.', 'woocommerce'),
                        'type' => 'title',
                    ),
                    'nashlink_checkout_merchant_info' => array(
                        'description' => __('You need Nash Link Merchant API keys to use this plugin, you can create one on your <br><a href = "https://link.nash.io/developers" target = "_blank">Nash Link Dashboard</a>.</p>', 'woocommerce'),
                        'type' => 'title',
                    ),
                    'description' => array(
                        'title' => __('Description', 'woocommerce'),
                        'type' => 'text',
                        'description' => __('This is the message box that will appear on the <b>checkout page</b> when they select Nash Link.', 'woocommerce'),
                        'default' => 'Bitcoin and cryptocurrency payments powered by Nash',

                    ),
                    'nashlink_checkout_api_key_sandbox' => array(
                        'title' => __('Sandbox API key', 'woocommerce'),
                        'label' => __('Sandbox API key', 'woocommerce'),
                        'type' => 'text',
                        'description' => 'Your <b>sandbox</b> merchant API key.  <a href = "https://link.nash.io/development" target = "_blank">Create one here</a>.',
                        'default' => '',

                    ),
                    'nashlink_checkout_api_secret_key_sandbox' => array(
                        'title' => __('Sandbox API secret key', 'woocommerce'),
                        'label' => __('Sandbox API secret key', 'woocommerce'),
                        'type' => 'text',
                        'description' => 'Your <b>sandbox</b> merchant API secret key.  <a href = "https://link.nash.io/development" target = "_blank">Create one here</a>.',
                        'default' => '',

                    ),
                    'nashlink_checkout_api_key_prod' => array(
                        'title' => __('Production API key', 'woocommerce'),
                        'label' => __('Production API key', 'woocommerce'),
                        'type' => 'text',
                        'description' => 'Your <b>production</b> merchant API key.  <a href = "https://link.nash.io/development" target = "_blank">Create one here</a>.',
                        'default' => '',

                    ),
                    'nashlink_checkout_api_secret_key_prod' => array(
                        'title' => __('Production API secret key', 'woocommerce'),
                        'label' => __('Production API secret key', 'woocommerce'),
                        'type' => 'text',
                        'description' => 'Your <b>production</b> merchant API secret key.  <a href = "https://link.nash.io/development" target = "_blank">Create one here</a>.',
                        'default' => '',

                    ),
                    'nashlink_checkout_environment' => array(
                        'title' => __('Environment', 'woocommerce'),
                        'type' => 'select',
                        'description' => __('Select <b>Sandbox</b> for testing the plugin, <b>Production</b> when you are ready to go live.'),
                        'options' => array(
                            'prod' => 'Production',
                            'sandbox' => 'Sandbox',
                        ),
                        'default' => 'sandbox',
                    ),
                    'nashlink_checkout_slug' => array(
                        'title' => __('Checkout Page', 'woocommerce'),
                        'type' => 'text',
                        'description' => __('By default, this will be "checkout".  If you have a different Checkout page, enter the <b>page slug</b>. <br>ie. ' . get_home_url() . '/<b>checkout</b><br><br>View your pages <a target = "_blank" href  = "/wp-admin/edit.php?post_type=page">here</a>, your current checkout page should have <b>Checkout Page</b> next to the title.<br><br>Click the "quick edit" and copy and paste a custom slug here if needed.', 'woocommerce'),

                        'default' => 'checkout',
                    ),
                    'nashlink_checkout_capture_email' => array(
                        'title' => __('Auto-Capture Email', 'woocommerce'),
                        'type' => 'select',
                        'description' => __('Should Nash Link try to auto-add the client\'s email address?  If <b>Yes</b>, the client will not be able to change the email address on the Nash Link invoice.  If <b>No</b>, they will be able to add their own email address when paying the invoice.', 'woocommerce'),
                        'options' => array(
                            '1' => 'Yes',
                            '0' => 'No',
                        ),
                        'default' => '1',
                    ),
                    'nashlink_checkout_error' => array(
                        'title' => __('Error handling', 'woocommerce'),
                        'type' => 'text',
                        'description' => __('If there is an error with creting the invoice, enter the <b>page slug</b>. <br>ie. ' . get_home_url() . '/<b>error</b><br><br>View your pages <a target = "_blank" href  = "/wp-admin/edit.php?post_type=page">here</a>,.<br><br>Click the "quick edit" and copy and paste a custom slug here.', 'woocommerce'),
                    ),
                    'nashlink_checkout_order_process_confirmed_status' => array(
                        'title' => __('Nash Link Confirmed Invoice Status', 'woocommerce'),
                        'type' => 'select',
                        'description' => __('Map the Nash Link <b>paid</b> invoice status to one of the available WooCommerce order states.<br>All WooCommerce status options are listed here for your convenience.', 'woocommerce'),
                       'options' =>$wc_statuses_arr,
                        'default' => 'wc-processing',
                    ),
                    'nashlink_checkout_order_process_complete_status' => array(
                        'title' => __('Nash Link Complete Invoice Status', 'woocommerce'),
                        'type' => 'select',
                        'description' => __('Map the Nash Link <b>complete</b> invoice status to one of the available WooCommerce order states.<br>All WooCommerce status options are listed here for your convenience.', 'woocommerce'),
                       'options' =>$wc_statuses_arr,
                        'default' => 'wc-processing',
                    ),
                    'nashlink_checkout_order_expired_status' => array(
                        'title' => __('Nash Link Expired Status', 'woocommerce'),
                        'type' => 'select',
                        'description' => __('If set to <b>Yes</b>,  automatically set the order to canceled when the invoice has expired and has been notified by the Nash Link IPN.', 'woocommerce'),
                       
                        'options' => array(
                            '0'=>'No',
                            '1'=>'Yes'
                        ),
                        'default' => '0',
                    ),

                    'nashlink_log_mode' => array(
                        'title' => __('Developer Logging', 'woocommerce'),
                        'type' => 'select',
                        'description' => __('Errors will be logged to the plugin <b>log</b> directory automatically.  Set to <b>Enabled</b> to also log transactions, ie invoices and IPN updates', 'woocommerce'),
                        'options' => array(
                            '0' => 'Disabled',
                            '1' => 'Enabled',
                        ),
                        'default' => '1',
                    ),

                );
            }
            
            function process_payment($order_id)
            {
                #this is the one that is called intially when someone checks out
                global $woocommerce;
                $order = new WC_Order($order_id);
                // Return thankyou redirect
                return array(
                    'result' => 'success',
                    'redirect' => $this->get_return_url($order),
                );
            }
        } // end \WC_Gateway_Offline class
    } //end check for class existence
    else {
            global $wpdb;
            if (!function_exists('get_plugins')) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }

            $plugins_url = admin_url('plugins.php');

            $plugins = get_plugins();
            foreach ($plugins as $file => $plugin) {

                if ('Nash Link Checkout for WooCommerce' === $plugin['Name'] && true === is_plugin_active($file)) {

                    deactivate_plugins(plugin_basename(__FILE__));
                    wp_die('WooCommerce needs to be installed and activated before Nash Link Checkout for WooCommerce can be activated.<br><a href="' . $plugins_url . '">Return to plugins screen</a>');

                }
            }

        }

    }


//update the order_id field in the custom table, try and create the table if this is called before the original
add_action('admin_notices', 'nashlink_checkout_update_db_1');
function nashlink_checkout_update_db_1()
{
    if (!array_key_exists('section',$_GET)) {
        return;
    }
    if ($_GET['section'] == 'nashlink_checkout_gateway'  && is_admin()):
    
        if(get_option('nashlink_wc_checkout_db1') != 1):
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $table_name = '_nashlink_checkout_transactions';
       
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name(
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `order_id` varchar(255) NOT NULL,
            `transaction_id` varchar(255) NOT NULL,
            `transaction_status` varchar(50) NOT NULL DEFAULT 'new',
            `date_added` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
            ) $charset_collate;";

        dbDelta($sql);
        $sql = "ALTER TABLE `$table_name` CHANGE `order_id` `order_id` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL; ";
        $wpdb->query($sql);
        update_option('nashlink_wc_checkout_db1',1);
        endif;
      
    endif;
}

add_action('admin_notices', 'nashlink_checkout_check_token');
function nashlink_checkout_check_token()
{
    if (isset($_GET['section'])):
        if ($_GET['section'] == 'nashlink_checkout_gateway' && $_POST && is_admin()):
            if (!file_exists(plugin_dir_path(__FILE__) . 'logs')) {
                mkdir(plugin_dir_path(__FILE__) . 'logs', 0755, true);
            }
            $env_data = NPC_getEnvData();
            $nashlink_checkout_api_key = $env_data['api_key'];
            $nashlink_checkout_api_secret_key = $env_data['api_secret_key'];
            $nashlink_checkout_environment = $env_data['environment'];
            if (empty($nashlink_checkout_api_key) || empty($nashlink_checkout_api_secret_key)): ?>
<?php _e('There is no api keys set for your <b>' . strtoupper($nashlink_checkout_environment) . '</b> environment.  <b>Nash Link</b> will not function if api key and api secret key are not set.');?>
<?php
        ##check and see if the api keys are valid
        else:
            if ($_POST && !empty($nashlink_checkout_api_key) && !empty($nashlink_checkout_environment)) {
                if (!NPC_isValidNashLinkKeys($env_data['environment'], $env_data['api_key'], $env_data['api_secret_key'])): ?>
<div class="error notice">
    <p>
        <?php _e('The api keys for <b>' . strtoupper($nashlink_checkout_environment) . '</b> are not valid.  Please verify your settings.');?>
    </p>
</div>
<?php endif;
        }

    endif;

    endif;
    endif;

}

//redirect to cart if nashlink single page enabled
function np_redirect_to_checkout( $url ) {
    $url = get_permalink( get_option( 'woocommerce_checkout_page_id' ) ); 
    $url.='?payment=nashlink';
    return $url;
 }
#add_filter( 'woocommerce_add_to_cart_redirect', 'np_redirect_to_checkout' );

function nashlink_default_payment_gateway(){
    if( is_checkout() && ! is_wc_endpoint_url() ) {
        global $woocommerce;
        //unset($gateways['WC_Gateway_NashLink']);
        // HERE define the default payment gateway ID
        $default_payment_id = 'nashlink_checkout_gateway';
        if(isset($_GET['payment']) && $_GET['payment'] == 'nashlink'):
            WC()->session->set( 'chosen_payment_method', $default_payment_id );
        endif;
    }
}

add_action( 'template_redirect', 'nashlink_default_payment_gateway' );

#http://<host>/wp-json/nashlink/ipn/status
add_action('rest_api_init', function () {
    register_rest_route('nashlink/ipn', '/status', array(
        'methods' => 'POST,GET',
        'callback' => 'nashlink_checkout_ipn',
        'permission_callback' => function () {
            return true;
        },
    ));
});

//http://<host>/wp-json/nashlink/ipn/status
function nashlink_checkout_ipn(WP_REST_Request $request)
{
    global $woocommerce;
    
    WC()->frontend_includes();
    WC()->cart = new WC_Cart();
    WC()->session = new WC_Session_Handler();
    WC()->session->init();
    #$hash_key = $_REQUEST['hash_key'];
    $data = $request->get_body();

    $data = json_decode($data);
    $event = $data->event;
    $data = $data->data;
    
    $invoiceID = $data->id;
    $orderid = nashlink_checkout_get_order_id_nashlink_invoice_id($invoiceID);
    $order_status = $data->status;

    NPC_Logger($data, 'INCOMING IPN', true);

    $order = new WC_Order($orderid);
    if ($order->get_payment_method() != 'nashlink_checkout_gateway'){
        #ignore the IPN when the order payment method is (no longer) nashlink
        NPC_Logger("Order id = ".$orderid.", Nash Link invoice id = ".$invoiceID.". Current payment method = " . $order->get_payment_method(), 'Ignore IPN', true);
        die();
    }   

    #verify the ipn matches the status of the actual invoice

    if (nashlink_checkout_get_order_transaction($orderid, $invoiceID) == 1):
      
        $nashlink_checkout_options = get_option('woocommerce_nashlink_checkout_gateway_settings');
        $nashlink_checkout_order_process_confirmed_status = $nashlink_checkout_options['nashlink_checkout_order_process_confirmed_status'];        
        $nashlink_checkout_order_process_complete_status = $nashlink_checkout_options['nashlink_checkout_order_process_complete_status'];
        $nashlink_checkout_order_expired_status = $nashlink_checkout_options['nashlink_checkout_order_expired_status'];

        $nashlink_checkout_environment = $nashlink_checkout_options['nashlink_checkout_environment'];

        $params = new stdClass();
        $params->extension_version = NPC_getNashLinkVersionInfo();
        $params->invoiceID = $invoiceID;

        // get invoice status from nash link side
        $env_data = NPC_getEnvData();
        $api = new NashLinkApi($env_data['environment'], $env_data['api_key'], $env_data['api_secret_key']);
        $orderStatus = $api->getInvoice($invoiceID);

        $invoice_status = $orderStatus['data']['status'];
        
        # check if nashlink status is the same of ipn call
        if ($invoice_status != $order_status) {
            // set order status as fraud suspect?
            NPC_Logger('Mismatch status for order: #' . $invoiceID, 'INCOMING IPN', true);
            die();
        }

        #update the lookup table
        $note_set = null;
             
        nashlink_checkout_update_order_note($orderid, $invoiceID, $order_status);
        $wc_statuses_arr = wc_get_order_statuses();
        $wc_statuses_arr['nashlink-ignore'] = "Do not change status";

        switch ($order_status) {
         
            case 'paid':
                if($nashlink_checkout_order_process_confirmed_status !='nashlink-ignore'):
                    $lbl = $wc_statuses_arr[$nashlink_checkout_order_process_confirmed_status];
                    if(!isset($lbl)):
                        $lbl = "Processing";
                        $nashlink_checkout_order_process_confirmed_status = 'wc-processing';
                    endif;
    
                    $order->add_order_note('Nash Link Invoice ID: <a target = "_blank" href = "' . $api->getServerUri() . "/invoices/" . $invoiceID . '">' . $invoiceID . '</a> is confirmed and processing');
                    $order_status =$nashlink_checkout_order_process_confirmed_status;
                    $order->update_status($order_status, __('Nash Link payment processing', 'woocommerce'));
                else:
                    $order->add_order_note('Nash Link Invoice ID: <a target = "_blank" href = "' . $api->getServerUri() . "/invoices/" . $invoiceID . '">' . $invoiceID . '</a> is confirmed and processing. The order status has not been updated due to your settings.');    
                endif;
                WC()->cart->empty_cart();
                wc_reduce_stock_levels($orderid);
                break;

            case 'complete':
                if($nashlink_checkout_order_process_complete_status !='nashlink-ignore'):
                    $lbl = $wc_statuses_arr[$nashlink_checkout_order_process_complete_status];
                    if(!isset($lbl)):
                        $lbl = "Processing";
                        $nashlink_checkout_order_process_complete_status = 'wc-processing';
                    endif;
                    
                    $order_status =$nashlink_checkout_order_process_complete_status;
                    $order->add_order_note('Nash Link Invoice ID: <a target = "_blank" href = "' . $api->getServerUri() . "/invoices/" . $invoiceID . '">' . $invoiceID . '</a> has been paid.');
                    $order->update_status($order_status, __('Nash Link payment completed', 'woocommerce'));
                    // Reduce stock levels
                    wc_reduce_stock_levels($orderid);
                    // Remove cart
                    WC()->cart->empty_cart();
                else:
                    $order->add_order_note('Nash Link Invoice ID: <a target = "_blank" href = "' . $api->getServerUri() . "/invoices/" . $invoiceID . '">' . $invoiceID . '</a> has been paid. The order status has not been updated due to your settings.');
                endif;
                break;

            case 'expired':
                $order_status = "wc-cancelled";
                $order->add_order_note('Nash Link Invoice ID: <a target = "_blank" href = "' . $api->getServerUri() . "/invoices/" . $invoiceID . '">' . $invoiceID . '</a> has expired.');
                $order->update_status($order_status, __('Nash Link payment expired', 'woocommerce'));
                break;

            case 'refund':
                $order->add_order_note('Nash Link Invoice ID: <a target = "_blank" href = "' . $api->getServerUri() . "/invoices/" . $invoiceID . '">' . $invoiceID . ' </a> has been refunded.');
                $order->update_status('wc-refunded', __('Nash Link payment refunded', 'woocommerce'));
                break;

            default:
                break;
        }
        die();
    endif;
}

add_action('template_redirect', 'nash_woo_custom_redirect_after_purchase');
function nash_woo_custom_redirect_after_purchase()
{

    global $wp;
    $nashlink_checkout_options = get_option('woocommerce_nashlink_checkout_gateway_settings');

    if (is_checkout() && !empty($wp->query_vars['order-received'])) {

        $order_id = $wp->query_vars['order-received'];

        try {
            $order = new WC_Order($order_id);
           
            //this means if the user is using nashlink AND this is not the redirect
            $show_nashlink = true;

            if (isset($_GET['redirect']) && $_GET['redirect'] == 'false'):
                $show_nashlink = false;
                $invoiceID = $_COOKIE['nashlink-invoice-id'];

                //clear the cookie
                setcookie("nashlink-invoice-id", "", time() - 3600);
            endif;

            if ($order->get_payment_method() == 'nashlink_checkout_gateway' && $show_nashlink == true):
                //sample values to create an item, should be passed as an object'
                $params = new stdClass();
                $current_user = wp_get_current_user();
               
                $params->extension_version = NPC_getNashLinkVersionInfo();
                $params->price = $order->get_total();
                $params->currency = $order->get_currency(); //set as needed
                if ($nashlink_checkout_options['nashlink_checkout_capture_email'] == 1):
                    $current_user = wp_get_current_user();

                    if ($current_user->user_email):
                        $buyerInfo = new stdClass();
                        $buyerInfo->name = $current_user->display_name;
                        $buyerInfo->email = $current_user->user_email;
                        $params->buyer = $buyerInfo;
                    endif;
                endif;

                //orderid
                $params->orderId = $order->get_order_number($order_id);
               
                //redirect and ipn stuff
                $checkout_slug = $nashlink_checkout_options['nashlink_checkout_slug'];
                if (empty($checkout_slug)):
                    $checkout_slug = 'checkout';
                endif;
                $params->redirectURL = get_home_url() . '/' . $checkout_slug . '/order-received/' . $order_id . '/?key=' . $order->get_order_key() . '&redirect=false';
                //$params->acceptanceWindow = 1200000;
                $params->notificationURL = get_home_url() . '/wp-json/nashlink/ipn/status';

                #http://<host>/wp-json/nashlink/ipn/status
                $params->extendedNotifications = true;

                // create the invoice on nash link side
                $env_data = NPC_getEnvData();
                $api = new NashLinkApi($env_data['environment'], $env_data['api_key'], $env_data['api_secret_key']);
                $invoiceData = $api->createinvoice($params);

                #NPC_Logger(json_decode(invoiceData), 'NEW NASHLINK INVOICE',true);

                //$invoiceData = json_decode(invoiceData);

                if ($invoiceData['error'] == true):
                    $nashlink_checkout_options = get_option('woocommerce_nashlink_checkout_gateway_settings');
                    $errorURL = get_home_url().'/'.$nashlink_checkout_options['nashlink_checkout_error'];
                    $order_status = "wc-cancelled";
                    $order = new WC_Order($order_id);
                    $items = $order->get_items();
                    $order->update_status($order_status, __($invoiceData['message'].'.', 'woocommerce'));

                     //clear the cart first so things dont double up
                    WC()->cart->empty_cart();
                    foreach ($items as $item) {
                        //now insert for each quantity
                        $item_count = $item->get_quantity();
                        for ($i = 0; $i < $item_count; $i++):
                            WC()->cart->add_to_cart($item->get_product_id());
                        endfor;
                    }
                    wp_redirect($errorURL);
                    die();
                endif; 
              
                NPC_Logger(serialize($invoiceData), 'NEW NASHLINK INVOICE', true);
                //now we have to append the invoice transaction id for the callback verification
                
                $invoiceID = $invoiceData['data']['id'];
                //set a cookie for redirects and updating the order status
                $cookie_name = "nashlink-invoice-id";
                $cookie_value = $invoiceID;
                setcookie($cookie_name, $cookie_value, time() + (86400 * 30), "/");

                $nashlink_checkout_options = get_option('woocommerce_nashlink_checkout_gateway_settings');
                
                #insert into the database
                nashlink_checkout_insert_order_note($order_id, $invoiceID);
                
                wp_redirect($invoiceData['data']['url']);
                
                exit;
            endif;
        } catch (Exception $e) {
            global $woocommerce;
            $cart_url = $woocommerce->cart->get_cart_url();
            wp_redirect($cart_url);
            exit;
        }
    }
}
// Replacing the Place order 
add_filter('woocommerce_order_button_html', 'nashlink_checkout_replace_order_button_html', 10, 2);
function nashlink_checkout_replace_order_button_html($order_button, $override = false)
{
    if ($override):
        return;
    else:
        return $order_button;
    endif;
}

function NPC_getNashLinkVersionInfo($clean = null)
{
    $plugin_data = get_file_data(__FILE__, array('Version' => 'Version', 'Plugin_Name' => 'Plugin Name'), false);
    $plugin_name = $plugin_data['Plugin_Name'];
    if ($clean):
        $plugin_version = $plugin_name . ' ' . $plugin_data['Version'];
    else:
        $plugin_name = str_replace(" ", "_", $plugin_name);
        $plugin_name = str_replace("_for_", "_", $plugin_name);
        $plugin_version = $plugin_name . '_' . $plugin_data['Version'];
    endif;
   
    return $plugin_version;
}

function NPC_isValidNashLinkKeys($environment, $api_key, $api_secret_key)
{
    // implements endpoint signature check?
    // https://link.nash.io/invoices/1?api_key=...& ?
    return true;
}

#custom info for Nash Link
add_action('woocommerce_thankyou', 'nashlink_checkout_custom_message');
function nashlink_checkout_custom_message($order_id)
{
    $order = new WC_Order($order_id);
    if ($order->get_payment_method() == 'nashlink_checkout_gateway'):        
        // get invoice status from nash link side
        $env_data = NPC_getEnvData();
        $api = new NashLinkApi($env_data['environment'], $env_data['api_key'], $env_data['api_secret_key']);
        $orderStatus = $api->getInvoice($order->get_transaction_id());

        if ($orderStatus['error'] == true) {
            $checkout_message = 'Error trying to retrieve nashlink invoice: ' . $orderStatus['message'];
        } else {
            switch ($orderStatus['data']['status']) {
                case 'new':
                case 'paid':
                    $checkout_message = 'Your payment is being processed. We will notify you when it has been approved.';
                    break;
                case 'complete':
                    $checkout_message = 'Your payment was approved!';
                    break;
                case 'expired':
                    $checkout_message = 'Your cryptocurrency quote expired. Please place your order again.';
                    break;
                case 'invalid':
                    $checkout_message = 'There was a problem with your payment. You are being sent a refund.';
                    break;
                default:
                    $checkout_message = '';
                    break;
            }
        }

        if ($checkout_message != ''):
            echo '<hr><b>' . $checkout_message . '</b><br><br><hr>';
        endif;

    endif;
}

#add the gatway to woocommerce
add_filter('woocommerce_payment_gateways', 'wc_nashlink_checkout_add_to_gateways');
function wc_nashlink_checkout_add_to_gateways($gateways)
{
    $gateways[] = 'WC_Gateway_NashLink';
    return $gateways;
}