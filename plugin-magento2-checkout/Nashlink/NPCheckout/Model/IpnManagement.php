<?php

namespace Nashlink\NPCheckout\Model;

use Magento\Sales\Model\Order;

use Nashlink\NPCheckout\Api\NashLinkApi;

class IpnManagement implements \Nashlink\NPCheckout\Api\IpnManagementInterface
{

    protected $_invoiceService;
    protected $_transaction;
    public $orderRepository;

    public $apiToken;
    public $apiSecret;
    public $env;

    protected $_api;
    private $_logger;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\ResponseFactory $responseFactory,
        \Magento\Framework\UrlInterface $url,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Framework\DB\Transaction $transaction,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->_moduleList = $moduleList;

        $this->_scopeConfig = $scopeConfig;
        $this->_responseFactory = $responseFactory;
        $this->_url = $url;
        $this->orderRepository = $orderRepository;
        $this->_invoiceService = $invoiceService;
        $this->_transaction = $transaction;
        $this->_logger = $logger;
    
        $this->env = $this->getStoreConfig('payment/npcheckout/nashlink_endpoint');
        if ($this->env == 'prod'):
            $this->apiToken = $this->getStoreConfig('payment/npcheckout/nashlink_prodtoken');
            $this->apiSecret = $this->getStoreConfig('payment/npcheckout/nashlink_prodsecret');
        else:
            $this->apiToken = $this->getStoreConfig('payment/npcheckout/nashlink_devtoken');
            $this->apiSecret = $this->getStoreConfig('payment/npcheckout/nashlink_devsecret');
        endif;
        $this->_api = new NashLinkApi($this->env, $this->apiToken, $this->apiSecret);
    }

    public function getStoreConfig($_env)
    {
        return $this->_scopeConfig->getValue($_env, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function NPC_Invoice($item){
        $this->item = $item;
        return $item;
    }

    public function checkInvoiceStatus($orderID)
    {
        return $this->_api->getInvoice($orderID);
    }

    public function getOrder($_order_id)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $order = $objectManager->create('Magento\Sales\Api\Data\OrderInterface')->loadByIncrementId($_order_id);
        return $order;
    } 

    public function postIpn()
    {
        try {
            #database
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); // Instance of object manager
            $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
            $connection = $resource->getConnection();
            $table_name = $resource->getTableName('nashlink_transactions');
            
            #json ipn
            $all_data = json_decode(file_get_contents("php://input"), true);
            $data = $all_data['data'];
            $orderid = $data['orderId'];
            $order_status = $data['status'];
            $order_invoice = $data['id'];

            #is it in the lookup table
            $sql = $connection->select()
                                        ->from($table_name)
                                        ->where('order_id = ?', $orderid)
                                        ->where('transaction_id = ?', $order_invoice);
            $row = $connection->fetchAll($sql);
            
            if ($row):
                #verify the ipn                
                //$nashlink_ipn_mapping = $this->getStoreConfig('payment/npcheckout/nashlink_ipn_mapping');
            
                $config = (new \stdClass());
                $params = (new \stdClass());

                $params->invoiceID = $order_invoice;
                $params->extension_version = $this->getExtensionVersion();
            
                $invoice = $this->NPC_Invoice($params);

                $orderStatus = $this->checkInvoiceStatus($order_invoice);
                // errors?
                if ($orderStatus['error'] == true) {
                    $this->_logger->debug('INCOMING IPN ERROR: ' . $orderStatus['message'] . ' status code: ' . $orderStatus['status_code']);
                    return false;
                }

                $invoice_status = $orderStatus['data']['status'];
                    
                # check if nashlink status is not the same of ipn call
                if ($invoice_status != $order_status) {
                    $this->_logger->debug('INCOMING IPN WARNING: Mismatch status for order: #' . $order_invoice);
                    return false;
                }

                $update_data = array('transaction_status' => $invoice_status);
                $update_where = array(
                    'order_id = ?' => $orderid,
                    'transaction_id = ?' => $order_invoice
                );

                $connection->update($table_name,$update_data,$update_where);
                $order = $this->getOrder($orderid);
                
                $this->_logger->debug("Order #" . $orderid . ": " . $order_status);

                switch ($order_status) {
                    case 'paid':
                        $order->addStatusHistoryComment('NashLink Invoice <a href = "' . $this->_api->getServerUri() . '/invoices/' . $order_invoice . '" target = "_blank">' . $order_invoice . '</a> is processing.');
                        $order->setState(Order::STATE_PROCESSING)->setStatus(Order::STATE_PROCESSING);
                        $order->save();
                        return true;
                        break;
                    case 'complete':
                        $order->addStatusHistoryComment('NashLink Invoice <a href = "' . $this->_api->getServerUri() . '/invoices/' . $order_invoice . '" target = "_blank">' . $order_invoice . '</a> status has changed to Completed.');
                        $order->setState(Order::STATE_COMPLETE)->setStatus(Order::STATE_COMPLETE);
                        $order->save();
                        $this->createMGInvoice($order);
                        return true;
                        break;
                    case 'expired':
                        $order->addStatusHistoryComment('NashLink Invoice <a href = "' . $this->_api->getServerUri() . '/invoices/' . $order_invoice . '" target = "_blank">' . $order_invoice . '</a> has expired.');
                        $order->setState(Order::STATE_CANCELED)->setStatus(Order::STATE_CANCELED);
                        $order->save();
                        return true;
                        break;
                    case 'invalid':
                        $order->addStatusHistoryComment('NashLink Invoice <a href = "' . $this->_api->getServerUri() . '/invoices/' . $order_invoice . '" target = "_blank">' . $order_invoice . '</a> has been refunded.');
                        $order->setState(Order::STATE_CLOSED)->setStatus(Order::STATE_CLOSED);
                        $order->save();
                        return true;
                        break;
                }

            endif;
        } catch (Exception $e) {
            $this->_logger->debug('INCOMING IPN ERROR: ' . $e->getMessage());
            return false;
        }
    }

    public function createMGInvoice($order)
    {
        try{
            $invoice = $this->_invoiceService->prepareInvoice($order);
            $invoice->register();
            $invoice->save();
            $transactionSave = $this->_transaction->addObject(
                $invoice
            )->addObject(
                $invoice->getOrder()
            );
            $transactionSave->save();
        } catch (Exception $e) {
        
        }
    }
    public function getExtensionVersion()
    {
        return 'Nashlink_NPCheckout_Magento2_3.08.2020.1';
    }
}
