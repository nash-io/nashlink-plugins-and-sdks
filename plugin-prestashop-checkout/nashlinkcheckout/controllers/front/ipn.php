<?php
require_once __DIR__ . '/../AbstractRestController.php';
require_once __DIR__ . '/../../lib/NashLinkApi.php';

use Nash\Link\NashLinkApi;

class NashlinkCheckoutIpnModuleFrontController extends AbstractRestController
{
    protected function processGetRequest()
    {
        // do something then output the result
        $this->ajaxDie(json_encode([
            'success' => true,
            'operation' => 'get',
            'message' => 'You should not be here',
        ]));
    }

    protected function processPostRequest()
    {
        $all_data = json_decode(file_get_contents("php://input"), true);
        $db_prefix = _DB_PREFIX_;
        
        $data = $all_data['data'];
        $orderId = $data['orderId'];
        $invoiceID = $data['id'];
        $order_status = $data['status'];

        $table_name = '_nashlink_checkout_transactions';
        $order_table = $db_prefix . 'orders';
        $order_history_table = $db_prefix . 'order_history';

        #NASHLINK SPECIFIC INFO
        $env = 'sandbox';
        $nashlink_api_key = Configuration::get('nashlink_checkout_api_key_sandbox');
        $nashlink_api_secret_key = Configuration::get('nashlink_checkout_api_secret_key_sandbox');

        if (Configuration::get('nashlink_checkout_endpoint') == 1):
            $env = 'prod';
            $nashlink_api_key = Configuration::get('nashlink_checkout_api_key_prod');
            $nashlink_api_secret_key = Configuration::get('nashlink_checkout_api_secret_key_prod');
        endif;

        // get invoice status from nash link side
        $api = new NashLinkApi($env, $nashlink_api_key, $nashlink_api_secret_key);
        $orderStatus = $api->getInvoice($invoiceID);
        
        if ($orderStatus['error']) {
            die($orderStatus['message']);
        }

        $invoice_status = $orderStatus['data']['status'];
        
        # check if nashlink status is the same of ipn call
        if ($invoice_status != $order_status) {
            // set order status as fraud suspect?
            die('Mismatch status for order: #' . $invoiceID . 'INCOMING IPN');
        }

        $bp_sql = "SELECT * FROM " . $table_name . " WHERE transaction_id = '$invoiceID' AND order_id = $orderId";
        $db = Db::getInstance();

        if ($results = $db->ExecuteS($bp_sql)):
            switch ($order_status) {
                case 'complete': #complete
              
                    $current_state = Configuration::get('nashlink_checkout_ipn_map_confirmed');
                    if($current_state == ''){
                        $current_state = 7; // payment confirmed
                    }
                    $current_state = (int)$current_state;
                    
                    #update the order and history
                    #$db = Db::getInstance();
                    #$bp_u = "UPDATE $order_table SET current_state = $current_state WHERE id_order = '$orderId'";
                    #$db->Execute($bp_u);
                    
                    $objOrder = new Order($orderId); //order with id=1
                    $history = new OrderHistory();
                    $history->id_order = $orderId;
                    $history->changeIdOrderState($current_state, (int)($objOrder->id));


                    #update the transaction table
                    $bp_t = "UPDATE $table_name SET transaction_status = '$order_status' WHERE transaction_id = '$invoiceID' AND order_id = $orderId";
                    $db->Execute($bp_t);

                    #update the history table
                    
                    $bp_h = "INSERT INTO $order_history_table (id_employee,id_order,id_order_state,date_add)
                                            VALUES (0,'$orderId',$current_state,NOW())";
                    $db->Execute($bp_h);
                    
                    $email_order = new Order((int)$orderId);
                    $email_customer = new Customer((int)$email_order->id_customer);
                    #print_r($email_customer);
                    break;

                case 'paid': #pending
                    #update the order and history
                    $current_state = Configuration::get('nashlink_checkout_order_map_created');
                    if($current_state == ''){
                        $current_state = 7; // payment confirmed
                    }
                    $current_state = (int)$current_state;
                    $db = Db::getInstance();
                    $bp_u = "UPDATE $order_table SET current_state = $current_state WHERE id_order = '$orderId'";
                    $db->Execute($bp_u);

                    #update the transaction table
                    $bp_t = "UPDATE $table_name SET transaction_status = '$order_status' WHERE transaction_id = '$invoiceID' AND order_id = $orderId";
                    $db->Execute($bp_t);

                    #update the history table
                    $bp_h = "INSERT INTO $order_history_table (id_employee,id_order,id_order_state,date_add)
                                        VALUES (0,'$orderId',$current_state,NOW())";
                    $db->Execute($bp_h);
                    break;

                case 'expired':
                    //delete the previous order
                    #update the order and history
                    $current_state = Configuration::get('nashlink_checkout_ipn_map');
                    if($current_state == ''){
                        $current_state = 3; // canceled
                    }
                    $current_state = (int)$current_state;
                    
                    $db = Db::getInstance();
                    $bp_u = "UPDATE $order_table SET current_state = $current_state WHERE id_order = '$orderId'";
                    $db->Execute($bp_u);

                    #update the transaction table
                    $bp_t = "UPDATE $table_name SET transaction_status = '$order_status' WHERE transaction_id = '$invoiceID' AND order_id = $orderId";
                    $db->Execute($bp_t);

                    #update the history table
                    $bp_h = "INSERT INTO $order_history_table (id_employee,id_order,id_order_state,date_add)
                                            VALUES (0,'$orderId',$current_state,NOW())";
                    $db->Execute($bp_h);
                    break;

            }
        endif;
    }

}
