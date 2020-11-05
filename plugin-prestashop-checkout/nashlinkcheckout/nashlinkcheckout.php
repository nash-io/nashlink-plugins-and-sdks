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

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}
class NashlinkCheckout extends PaymentModule
{
    protected $_html = '';
    protected $_postErrors = array();

    public $details;
    public $owner;
    public $address;
    public $extra_mail_vars;

    public function __construct()
    {

        $this->name = 'nashlinkcheckout';
        $this->tab = 'payments_gateways';
        $this->version = '1.0';
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
        $this->author = 'NashLink';
        $this->need_instance = 1;
        $this->controllers = array('nashlinkorder');
        $this->is_eu_compatible = 1;

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('NashLink Checkout');
        $this->description = $this->l('Accepts Bitcoin payments via NashLink.');
        $this->confirmUninstall = $this->l('Are you sure you want to delete your details?');

        if (!count(Currency::checkPaymentCurrencies($this->id))) {
            $this->warning = $this->l('No currency has been set for this module.');
        }
    }

    public function getContent()
    {
        if (Tools::isSubmit('submit_nashlinkcheckout')) {
            $this->postProcess();
        }

        $this->context->smarty->assign(array('module_dir' => $this->_path));

        return $this->renderForm();
    }
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submit_nashlinkcheckout';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
        . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Define the input of the configuration form
     *
     * @see $this->renderForm
     *
     * @return array
     */
    protected function getConfigForm()
    {
        #$arr = array("Josh", "Shannon");
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Production Environment'),
                        'name' => 'nashlink_checkout_endpoint',
                        'is_bool' => true,
                        'desc' => $this->l('Choose between sandbox or production mode'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Production'),
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Sandbox'),
                            ),
                        ),
                    ),

                    array(
                        'type' => 'text',
                        'label' => $this->l('Sandbox API key'),
                        'name' => 'nashlink_checkout_api_key_sandbox',
                        'desc' => $this->l('Your sandbox merchant API key.  Create one @ https://link.nash.io/developers'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Sandbox API secret key'),
                        'name' => 'nashlink_checkout_api_secret_key_sandbox',
                        'desc' => $this->l('Your sandbox merchant API secret key.  Create one @ https://link.nash.io/developers'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Production API key'),
                        'name' => 'nashlink_checkout_api_key_prod',
                        'desc' => $this->l('Your production merchant API key.  Create one @ https://link.nash.io/developers'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Production API secret key'),
                        'name' => 'nashlink_checkout_api_secret_key_prod',
                        'desc' => $this->l('Your production merchant API secret key.  Create one @ https://link.nash.io/developers'),
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Map for Created orders'),
                        'name' => 'nashlink_checkout_order_map_created',
                        'desc' => $this->l('Map order status at the invoice creation.'),
                        'options' => array(
                            'query' => $this->getOrderStates(),
                            'id' => 'id_option',
                            'name' => 'name',

                        ),

                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('IPN Map for Confirmed orders'),
                        'name' => 'nashlink_checkout_ipn_map_confirmed',
                        'desc' => $this->l('Map order status after the invoice has been confirmed.'),
                        'options' => array(
                            'query' => $this->getOrderStates(),
                            'id' => 'id_option',
                            'name' => 'name',

                        ),

                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('IPN Map for Expired orders'),
                        'name' => 'nashlink_checkout_ipn_map',
                        'desc' => $this->l('Map order status if a NashLink invoice expires.'),
                        'options' => array(
                            'query' => $this->getOrderStates(),
                            'id' => 'id_option',
                            'name' => 'name',

                        ),

                    ),

                    array(
                        'type' => 'switch',
                        'label' => $this->l('Auto-Capture Email'),
                        'name' => 'nashlink_checkout_capture_email',
                        'desc' => $this->l('Should NashLink try to auto-add the client\'s email address?  If Yes, the client will not be able to change the email address on the NashLink invoice.  If No, they will be able to add their own email address when paying the invoice.'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Yes'),
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('No'),
                            ),
                        ),

                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Error Page'),
                        'name' => 'nashlink_checkout_error',
                        'desc' => $this->l('Add a URL for a user to be redirected to if there is an error'),

                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Error States'),
                        'name' => 'nashlink_checkout_error_state',
                        'desc' => $this->l('If there is an error with the order, what state should the order be mapped to?'),
                        'options' => array(
                            'query' => $this->getOrderStates(),
                            'id' => 'id_option',
                            'name' => 'name',

                        ),

                    ),

                ),

                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }
    public function getOrderStates()
    {

        $sql = 'SELECT * FROM ps_order_state_lang ORDER BY name ASC';
        $results = Db::getInstance()->ExecuteS($sql);
        $i = 0;
        foreach ($results as $row) {
            $arr[$i]['id_option'] = $row['id_order_state'];
            $arr[$i]['name'] = $row['name'];
            $i++;
        }
        return $arr;
       
    }

    /**
     * Retrieve the current configuration values.
     *
     * @see $this->renderForm
     *
     * @return array
     */
    protected function getConfigFormValues()
    {
        return array(
            'nashlink_checkout_endpoint' => Configuration::get('nashlink_checkout_endpoint', true),
            'nashlink_checkout_api_key_sandbox' => Configuration::get('nashlink_checkout_api_key_sandbox', true),
            'nashlink_checkout_api_secret_key_sandbox' => Configuration::get('nashlink_checkout_api_secret_key_sandbox', true),
            'nashlink_checkout_api_key_prod' => Configuration::get('nashlink_checkout_api_key_prod', true),
            'nashlink_checkout_api_secret_key_prod' => Configuration::get('nashlink_checkout_api_secret_key_prod', true),
            'nashlink_checkout_capture_email' => Configuration::get('nashlink_checkout_capture_email', true),
            'nashlink_checkout_order_map_created' => Configuration::get('nashlink_checkout_order_map_created', true),
            'nashlink_checkout_ipn_map' => Configuration::get('nashlink_checkout_ipn_map', true),
            'nashlink_checkout_ipn_map_confirmed' => Configuration::get('nashlink_checkout_ipn_map_confirmed', true),
            'nashlink_checkout_error' => Configuration::get('nashlink_checkout_error', true),
            'nashlink_checkout_error_state' => Configuration::get('nashlink_checkout_error_state', true),
        );
    }

    /**
     * Logic to apply when the configuration form is posted
     *
     * @return void
     */
    public function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    public function install()
    {
        if (!parent::install() || !$this->registerHook('payment') || !$this->registerHook('paymentOptions') || !$this->registerHook('paymentReturn')) {
            return false;
        }
        $this->registerHook('displayHeader');

        #api endpoints
        $this->registerHook('moduleRoutes');

        $table_name = '_nashlink_checkout_transactions';

        $sql = "CREATE TABLE IF NOT EXISTS $table_name(
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `order_id` int(11) NOT NULL,
        `transaction_id` varchar(255) NOT NULL,
        `customer_key` varchar(255) NOT NULL,
        `transaction_status` varchar(50) NOT NULL DEFAULT 'new',
        `date_added` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
        )";
        $db = Db::getInstance();
        $db->Execute($sql);

        return true;
    }
    public function hookModuleRoutes()
    {
        return [
            'module-nashlinkcheckout-ipn' => [
                'rule' => 'nashlinkcheckout/ipn',
                'keywords' => [],
                'controller' => 'ipn',
                'params' => [
                    'fc' => 'module',
                    'module' => 'nashlinkcheckout',
                ],
            ],
            'module-nashlinkcheckout-cartfix' => [
                'rule' => 'nashlinkcheckout/cartfix',
                'keywords' => [],
                'controller' => 'cartfix',
                'params' => [
                    'fc' => 'module',
                    'module' => 'nashlinkcheckout',
                ],
            ],
        ];
    }

    public function uninstall()
    {
        $table_name = '_nashlink_checkout_transactions';
        $sql = "DROP TABLE $table_name";
        $db = Db::getInstance();
        $db->Execute($sql);

        #Configuration::deleteByName('nashlink_APIKEY');
        return parent::uninstall();
    }

    public function hookDisplayHeader()
    {
        //$this->context->controller->addJS($this->_path . 'js/nashlink_ps.js');
    }

    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }

        if (!$this->checkCurrency($params['cart'])) {
            return;
        }

        $payment_options = [
            $this->getOfflinePaymentOption(),
        ];

        return $payment_options;
    }

    public function checkCurrency($cart)
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }

    public function getOfflinePaymentOption()
    {
        $offlineOption = new PaymentOption();
        $offlineOption->setCallToActionText($this->l('NashLink Checkout'))
            ->setAction($this->context->link->getModuleLink($this->name, 'nashlinkorder', array(), true))
            ->setAdditionalInformation($this->context->smarty->fetch('module:nashlinkcheckout/views/templates/front/payment_infos.tpl'));
        #->setLogo(Media::getMediaPath(_PS_MODULE_DIR_.$this->name.'/payment.jpg'));

        return $offlineOption;
    }

    protected function generateForm()
    {
        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[] = sprintf("%02d", $i);
        }

        $years = [];
        for ($i = 0; $i <= 10; $i++) {
            $years[] = date('Y', strtotime('+' . $i . ' years'));
        }

        $this->context->smarty->assign([
            'action' => $this->context->link->getModuleLink($this->name, 'nashlinkorder', array(), true),
            'months' => $months,
            'years' => $years,
        ]);

        return $this->context->smarty->fetch('module:nashlinkcheckout/views/templates/front/payment_form.tpl');
    }
}
