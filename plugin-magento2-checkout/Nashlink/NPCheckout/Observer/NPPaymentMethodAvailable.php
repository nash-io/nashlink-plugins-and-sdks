<?php
 
namespace Nashlink\NPCheckout\Observer;
 
use Magento\Framework\Event\ObserverInterface;
 
 
class NPPaymentMethodAvailable implements ObserverInterface
{
    /**
     * payment_method_is_active event handler.
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig 
    ) {
       
        $this->_scopeConfig = $scopeConfig;
    }

    public function getStoreConfig($_env)
    {
        $_val = $this->_scopeConfig->getValue($_env, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        return $_val;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if($observer->getEvent()->getMethodInstance()->getCode()=="npcheckout") {
            $nashlink_token = '';
            $nashlink_secret = '';
            $env = $this->getStoreConfig('payment/npcheckout/nashlink_endpoint');
            if ($env == 'prod'):
                $nashlink_token = $this->getStoreConfig('payment/npcheckout/nashlink_prodtoken');
                $nashlink_secret = $this->getStoreConfig('payment/npcheckout/nashlink_prodsecret');
            else:
                $nashlink_token = $this->getStoreConfig('payment/npcheckout/nashlink_devtoken');
                $nashlink_secret = $this->getStoreConfig('payment/npcheckout/nashlink_devsecret');
            endif;
            if($nashlink_token == '' || $nashlink_secret == ''):
                #hide the payment method
                $checkResult = $observer->getEvent()->getResult();
                $checkResult->setData('is_available', false); //this is disabling the payment method at checkout page
            endif;
        }
    }
}
