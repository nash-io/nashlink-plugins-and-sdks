<?php
namespace Nashlink\NPCheckout\Observer;

use Magento\Framework\Event\ObserverInterface;

use Nashlink\NPCheckout\Api\NashLinkApi;

class NPRedirect implements ObserverInterface
{
    protected $checkoutSession;
    protected $resultRedirect;
    protected $url;
    protected $coreRegistry;
    protected $_redirect;
    protected $_response;
    public $orderRepository;
    protected $_invoiceService;
    protected $_transaction;
    protected $_messageManager;
    protected $_logger;
    protected $_api;

    private $apiToken;
    private $apiSecret;
    private $env;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig, \Magento\Framework\App\ResponseFactory $responseFactory,
        \Magento\Framework\UrlInterface $url,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Controller\ResultFactory $result,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\ActionFlag $actionFlag,
        \Magento\Framework\App\Response\RedirectInterface $redirect,
        \Magento\Framework\App\ResponseInterface $response,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Framework\DB\Transaction $transaction,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->coreRegistry = $registry;
        $this->_moduleList = $moduleList;
        $this->_scopeConfig = $scopeConfig;
        $this->_responseFactory = $responseFactory;
        $this->_url = $url;
        $this->checkoutSession = $checkoutSession;
        $this->resultRedirect = $result;
        $this->_actionFlag = $actionFlag;
        $this->_redirect = $redirect;
        $this->_response = $response;
        $this->orderRepository = $orderRepository;
        $this->_invoiceService = $invoiceService;
        $this->_transaction = $transaction;
        $this->_messageManager = $messageManager;
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

    public function getOrder($_order_id)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $order = $objectManager->create('Magento\Sales\Api\Data\OrderInterface')->load($_order_id);
        return $order;
    }

    public function getBaseUrl()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        return $storeManager->getStore()->getBaseUrl();
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $controller = $observer->getControllerAction();
        $this->_actionFlag->set('', \Magento\Framework\App\Action\Action::FLAG_NO_DISPATCH, true);

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); // Instance of object manager
        $level = 1;

        $order_ids = $observer->getEvent()->getOrderIds();
        $order_id = $order_ids[0];
        $order = $this->getOrder($order_id);
        $order_id_long = $order->getIncrementId();

        if ($order->getPayment()->getMethodInstance()->getCode() == 'npcheckout') {
            #set to pending and override magento coding
            $order->setState('new', true);
            $order->setStatus('pending', true);

            $order->save();

            #get the ux type, redirect only for now!
            $modal = false;
            //if ($this->getStoreConfig('payment/npcheckout/nashlink_ux') == 'modal'):
            //    $modal = true;
            //endif;

            //create an item, should be passed as an object'
            $params = (new \stdClass());
            //$params->extension_version = $this->getExtensionVersion();
            $params->price = $order['base_grand_total'];
            $params->currency = $order['base_currency_code']; //set as needed

            #buyer email
            $customerSession = $objectManager->create('Magento\Customer\Model\Session');

            $buyerInfo = (new \stdClass());
            $guest_login = true;
            if ($customerSession->isLoggedIn()) {
                $guest_login = false;
                $buyerInfo->name = $customerSession->getCustomer()->getName();
                $buyerInfo->email = $customerSession->getCustomer()->getEmail();
            } else {
                $buyerInfo->name = $order->getBillingAddress()->getFirstName() . ' ' . $order->getBillingAddress()->getLastName();
                $buyerInfo->email = $order->getCustomerEmail();
            }
            $params->buyer = $buyerInfo;

            $params->orderId = trim($order_id_long);

            #ipn
            if ($modal == false) {
                if ($guest_login) { #user is a guest
                    #leave alone
                    //if ($modal == false):
                        #this will send them back to the order/returns page to lookup
                        $params->redirectURL = $this->getBaseUrl() . 'sales/guest/form';
                        #set some info for guest checkout
                        setcookie('oar_order_id', $order_id_long, time() + (86400 * 30), "/"); // 86400 = 1 day
                        setcookie('oar_billing_lastname', $order->getBillingAddress()->getLastName(), time() + (86400 * 30), "/"); // 86400 = 1 day
                        setcookie('oar_email', $order->getCustomerEmail(), time() + (86400 * 30), "/"); // 86400 = 1 day
                    //else:
                        //$params->redirectURL = $this->getBaseUrl() . 'checkout/onepage/success/';
                    //endif;
                } else {
                    $params->redirectURL = $this->getBaseUrl() . 'sales/order/view/order_id/' . $order_id . '/';
                }
            }

            $params->notificationURL = $this->getBaseUrl() . 'rest/V1/nashlink-npcheckout/ipn';

            //this creates the invoice with all of the config params from the item
            $invoiceData = $this->_api->createInvoice($params);

            // errors?
            if ($invoiceData['error'] == true) {
                $msg = "Error processing the order #" . $order_id . ", please try to re-order, if the problem persist inform your merchant support team.";
                $this->_messageManager->addErrorMessage($msg);
                $this->_logger->debug('ERROR: cant create invoice for order #' . $order_id . '. message: ' . $invoiceData['message'] . ' status code: ' . $invoiceData['status_code'] . ' invoice data: ' . serialize($params));
                $order->addStatusHistoryComment('Error creating Nashlink invoice for order #' . $order_id . '. Canceling order...');
                $order->setState(Order::STATE_CLOSED)->setStatus(Order::STATE_CLOSED);
                $order->save();
            } else {
                //now we have to append the invoice transaction id for the callback verification
                $invoiceID = $invoiceData['data']['id'];

                #database
                $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
                $connection = $resource->getConnection();
                $table_name = $resource->getTableName('nashlink_transactions');

                $connection->insertForce(
                    $table_name,
                    ['order_id' => $order_id_long, 'transaction_id' => $invoiceID,'transaction_status'=>'new']
                );
        
                switch ($modal) {
                    case true:
                        $modal_obj = (new \stdClass());
                        $modal_obj->notificationURL = $params->notificationURL;
                        $modal_obj->invoiceID = $invoiceID;
                        $modal_obj->invoiceUrl = $invoiceData['data']['url'];
                        setcookie("env", $this->env, time() + (86400 * 30), "/");
                        setcookie("invoicedata", json_encode($modal_obj), time() + (86400 * 30), "/");
                        setcookie("modal", 1, time() + (86400 * 30), "/");
                        break;
                    case false:
                    default:
                        $this->_redirect->redirect($this->_response, $invoiceData['data']['url']);
                        break;
                }
            }
        }
    } //end execute function
    public function getExtensionVersion()
    {
        return 'Nashlink_NPCheckout_Magento2.3.5.0';

    }

}
