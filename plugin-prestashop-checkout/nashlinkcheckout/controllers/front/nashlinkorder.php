<?php
/*
 * 2007-2015 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author PrestaShop SA <contact@prestashop.com>
 *  @copyright  2007-2015 PrestaShop SA
 *  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */
require_once __DIR__ . '/../../lib/NashLinkApi.php';

use Nash\Link\NashLinkApi;

/**
 * @since 1.5.0
 */
class NashlinkCheckoutNashlinkorderModuleFrontController extends ModuleFrontController
{

    /**
     * @see FrontController::postProcess()
     */
    public function postProcess()
    {
        $cart = $this->context->cart;
        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        // Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'nashlinkcheckout') {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            die($this->module->l('This payment method is not available.', 'nashlinkorder'));
        }

        $this->context->smarty->assign([
            'params' => $_REQUEST,
        ]);

        $this->setTemplate('module:nashlinkcheckout/views/templates/front/payment_return.tpl');

        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }
        $currency = $this->context->currency;
        $total = (float) $cart->getOrderTotal(true, Cart::BOTH);
        $mailVars = array(

        );

        #NASHLINK SPECIFIC INFO
        $env = 'sandbox';
        $nashlink_api_key = Configuration::get('nashlink_checkout_api_key_sandbox');
        $nashlink_api_secret_key = Configuration::get('nashlink_checkout_api_secret_key_sandbox');

        if (Configuration::get('nashlink_checkout_endpoint') == 1):
            $env = 'prod';
            $nashlink_api_key = Configuration::get('nashlink_checkout_api_key_prod');
            $nashlink_api_secret_key = Configuration::get('nashlink_checkout_api_secret_key_prod');
        endif;
        
        global $cookie;
        $module = Module::getInstanceByName('nashlinkcheckout');
        $version = $module->version;
        $errorURL = Configuration::get('nashlink_checkout_error');
        $errorState = intval(Configuration::get('nashlink_checkout_error_state'));
        $currency = new CurrencyCore($cookie->id_currency);

        $this->module->validateOrder($cart->id, Configuration::get('PS_OS_BANKWIRE'), $total, $this->module->displayName, null, $mailVars, (int) $currency->id, false, $customer->secure_key);
        $orderId = (int) $this->module->currentOrder;

        $params = new stdClass();

        $params->extendedNotifications = true;
        $params->extension_version = 'NashLinkCheckout_PrestaShop_' . $version;
        $params->price = (float) $cart->getOrderTotal(true, Cart::BOTH);
        $params->currency = $currency->iso_code;
        $params->orderId = strval($orderId);

        #$params->acceptanceWindow = 1200000;
        #redirect
        $params->redirectURL = _PS_BASE_URL_ . __PS_BASE_URI__ . 'index.php?controller=order-detail&id_order=' . $this->module->currentOrder . '&key=' . $customer->secure_key;
        #ipn
        $params->notificationURL = _PS_BASE_URL_ . __PS_BASE_URI__ . 'module/nashlinkcheckout/ipn';
        
        if (Configuration::get('nashlink_checkout_capture_email') == 1):
            if ($customer->email):
                $buyerInfo = new stdClass();
                $buyerInfo->name = $customer->firstname . ' ' . $customer->lastname;
                $buyerInfo->email = $customer->email;
                $params->buyer = $buyerInfo;
            endif;
        endif;
        
        $db_prefix = _DB_PREFIX_;

        // create the invoice on nash link side
        $api = new NashLinkApi($env, $nashlink_api_key, $nashlink_api_secret_key);
        $invoiceData = $api->createinvoice($params);

        if ($invoiceData['error']):
            $order_table = $db_prefix.'orders';
            if($errorState == 0):
                $errorState = 8;
            endif;
            $bp_u = "UPDATE $order_table SET current_state = $errorState WHERE id_order = '$orderId' AND secure_key = '$customer->secure_key'";
            $db = Db::getInstance();
            $db->Execute($bp_u);
            //header("Location: ".$errorURL);
            die($invoiceData['message'] . ' > ' . json_encode($params));
        endif;
        $invoiceID = $invoiceData['data']['id'];

        $bp_sql = "INSERT INTO _nashlink_checkout_transactions (order_id,transaction_id,customer_key) VALUES ($orderId,'$invoiceID','$customer->secure_key')";
        $db = Db::getInstance();
        try{
        $db->Execute($bp_sql);
            #echo $bp_sql;
        }catch (Exception $e) {
            #die("Oh noes! There's an error in the query!");
        }

        $order_state = Configuration::get('nashlink_checkout_order_map_created');
        if ($order_state == '') {
            $order_state = 9; // processing
        }

        $order_table = $db_prefix.'orders';
        $bp_u = "UPDATE $order_table SET current_state = $order_state WHERE id_order = '$orderId' AND secure_key = '$customer->secure_key'";
        $db = Db::getInstance();
        $db->Execute($bp_u);

        $order_history_table = $db_prefix.'order_history';
        $bp_u = "INSERT INTO $order_history_table (id_employee,id_order,id_order_state,date_add)
					            VALUES (0,'$orderId',3,NOW())";
        $db = Db::getInstance();
        $db->Execute($bp_u);
        
        # redirect user
        Tools::redirect($invoiceData['data']['url']);
    }
    
    public function getCartInfo($orderid)
    {
        $db_prefix = _DB_PREFIX_;
        $order_table = $db_prefix.'orders';
        $bp_sql = "SELECT * FROM $order_table WHERE id_order = $orderid LIMIT 1";
        $results = Db::getInstance()->executes($bp_sql);
        if (count($results) == 1):
            return $results[0]['id_cart'];
        endif;
    }
}
