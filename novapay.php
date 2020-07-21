<?php
/**
 * License
 * @author mnemonic88uk
 * @copyright 2020 mnemonic88uk
 * @license https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

require_once(dirname(__FILE__).'/classes/autoload.php');

if (!defined('_PS_VERSION_')) {
    exit;
}

class NovaPay extends PaymentModule
{
    /**
     * @var NovaPayConfiguration
     */
    public $configuration;

    /**
     * @var array
     */
    protected $errors = array();
    
    public function __construct()
    {
        $this->name = 'novapay';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->author = 'NovaPay';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('NovaPay');
        $this->description = $this->l('Accept payments with NovaPay.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall this module?');
        $this->ps_versions_compliancy = array('min' => '1.6.1', 'max' => _PS_VERSION_);
        $this->configuration = new NovaPayConfiguration();
    }

    /**
     * @return bool
     */
    public function isPs17()
    {
        return version_compare(_PS_VERSION_, '1.7.0.0', '>=');
    }

    /**
     * @return bool
     */
    protected function registerHooks()
    {
        $hookList = array(
            'actionAdminControllerSetMedia',
            'displayAdminOrderLeft',
            'header',
            'orderConfirmation',
        );

        if ($this->isPs17()) {
            $hookList[] = 'paymentOptions';
        } else {
            $hookList[] = 'payment';
        }
        
        return $this->registerHook($hookList);
    }
    
    public function install()
    {
        return parent::install() &&
            (new NovaPayOrderStateManager())->createOrderStates() &&
            (new NovaPayTableManager())->createTables() &&
            $this->registerHooks() &&
            $this->configuration->initialize();
    }

    public function uninstall()
    {
        return $this->configuration->delete() &&
            (new NovaPayTableManager())->dropTables() &&
            parent::uninstall();
    }

    /**
     * @param mixed $value
     * @return bool
     */
    protected function isValidString($value)
    {
        return is_string($value) && trim($value);
    }

    /**
     * @return bool
     */
    protected function validateSettingsFormFields()
    {
        if (!$this->isValidString(Tools::getValue('merchant_id'))) {
            $this->errors[] = $this->l('Invalid merchant ID');
        }
        
        $merchantPrivateKey = Tools::getValue('merchant_private_key');
        if (($merchantPrivateKey && !$this->isValidString($merchantPrivateKey)) ||
            (!$merchantPrivateKey && !$this->configuration->getMerchantPrivateKey())) {
            $this->errors[] = $this->l('Invalid merchant private key');
        }

        if (!$this->isValidString(Tools::getValue('server_public_key'))) {
            $this->errors[] = $this->l('Invalid server public key');
        }

        return !count($this->errors);
    }

    /**
     * @return string
     */
    protected function getCurrentIndex()
    {
        return $this->context->link->getAdminLink('AdminModules', false).'&'.http_build_query(array(
            'configure' => $this->name,
            'tab_module' => $this->tab,
            'module_name' => $this->name,
        ));
    }

    /**
     * @return string|bool
     */
    protected function getToken()
    {
        return Tools::getAdminTokenLite('AdminModules');
    }

    protected function redirectWithConfirmation()
    {
        Tools::redirectAdmin($this->getCurrentIndex(false).'&conf=6&token='.$this->getToken());
    }

    protected function saveSettings()
    {
        if (!$this->validateSettingsFormFields()) {
            return;
        }

        $this->configuration
            ->setConnectionChecked(false)
            ->setMerchantId(trim(Tools::getValue('merchant_id')))
            ->setServerPublicKey(Tools::getValue('server_public_key'))
            ->setTwoStepPayment(Tools::getValue('two_step_payment'))
            ->setSandboxMode(Tools::getValue('sandbox_mode'));

        $merchantPrivateKey = Tools::getValue('merchant_private_key');
        if ($merchantPrivateKey) {
            $this->configuration->setMerchantPrivateKey(trim($merchantPrivateKey));
        }

        $client = new NovaPayClient(
            $this->configuration->getMerchantId(),
            $this->configuration->getMerchantPrivateKey(),
            $this->configuration->isSandboxMode()
        );

        $this->configuration->setConnectionChecked($client->checkConnection());
        
        $this->redirectWithConfirmation();
    }

    /**
     * @return string|bool|int
     */
    protected function isAllowEmployeeFormLang()
    {
        $allowEmployeeFormLang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG');
        if (!$allowEmployeeFormLang) {
            $allowEmployeeFormLang = 0;
        }

        return $allowEmployeeFormLang;
    }
    
    /**
     * @param string $submitAction
     * @param array $formFields
     * @param array $formFieldsValue
     * @return string
     */
    protected function generateForm($submitAction, array $formFields, array $formFieldsValue)
    {
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = $this->isAllowEmployeeFormLang();
        $helper->identifier = $this->identifier;
        $helper->submit_action = $submitAction;
        $helper->currentIndex = $this->getCurrentIndex(false);
        $helper->token = $this->getToken();
        $helper->tpl_vars = array(
            'fields_value' => $formFieldsValue,
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($formFields));
    }
    
    /**
     * @return array
     */
    protected function getSettingsFormFieldsValue()
    {
        return array(
            'merchant_id' => Tools::getValue(
                'merchant_id',
                $this->configuration->getMerchantId()
            ),
            'merchant_private_key' => Tools::getValue(
                'merchant_private_key',
                ''
            ),
            'server_public_key' => Tools::getValue(
                'server_public_key',
                $this->configuration->getServerPublicKey()
            ),
            'two_step_payment' => Tools::getValue(
                'two_step_payment',
                $this->configuration->isTwoStepPayment()
            ),
            'sandbox_mode' => Tools::getValue(
                'sandbox_mode',
                $this->configuration->isSandboxMode()
            ),
        );
    }
    
    protected function generateSettingsForm()
    {
        $merchantPrivateKey = $this->configuration->getMerchantPrivateKey();
        $input = array(
            array(
                'type' => 'text',
                'label' => $this->l('Merchant ID'),
                'name' => 'merchant_id',
                'required' => true,
            ),
        );

        if ($merchantPrivateKey) {
            $input[] = array(
                'type' => 'html',
                'name' => 'show_merchant_private_key_input_button',
                'html_content' => '<button type="button" id="show_merchant_private_key_input" class="btn btn-default">'.$this->l('Change merchant private key').'</button>',
            );
        }
        
        $input[] = array(
            'type' => 'textarea',
            'label' => $this->l('Merchant private key'),
            'name' => 'merchant_private_key',
            'required' => !$merchantPrivateKey,
        );

        $input[] = array(
            'type' => 'textarea',
            'label' => $this->l('Server public key'),
            'name' => 'server_public_key',
            'required' => true,
        );

        $input[] = array(
            'type' => 'switch',
            'label' => $this->l('Two-step payment'),
            'name' => 'two_step_payment',
            'is_bool' => true,
            'values' => array(
                array(
                    'id' => 'two_step_payment_on',
                    'value' => 1
                ),
                array(
                    'id' => 'two_step_payment_off',
                    'value' => 0
                )
            ),
        );

        $input[] = array(
            'type' => 'switch',
            'label' => $this->l('Sandbox mode'),
            'name' => 'sandbox_mode',
            'is_bool' => true,
            'values' => array(
                array(
                    'id' => 'sandbox_mode_on',
                    'value' => 1
                ),
                array(
                    'id' => 'sandbox_mode_off',
                    'value' => 0
                )
            ),
        );

        return $this->generateForm(
            'submitSettings',
            array(
                'form' => array(
                    'legend' => array(
                        'title' => $this->l('Settings'),
                        'icon' => 'icon-cogs'
                    ),
                    'input' => $input,
                    'submit' => array(
                        'title' => $this->l('Save'),
                    ),
                ),
            ),
            $this->getSettingsFormFieldsValue()
        );
    }

    public function getContent()
    {
        if (Tools::isSubmit('submitSettings')) {
            $this->saveSettings();
        }
        
        $html = '';

        if ($this->configuration->getMerchantId() &&
            $this->configuration->getMerchantPrivateKey() &&
            !$this->configuration->isConnectionChecked()) {
            $html .= $this->displayWarning($this->l('Failed to verify connection. The module is not available at the front office. Please make sure that the merchant details are correct.'));
        }

        if (count($this->errors)) {
            foreach ($this->errors as $error) {
                $html .= $this->displayError($error);
            }
        }
        
        $html .= $this->generateSettingsForm();

        return $html;
    }

    public function hookActionAdminControllerSetMedia()
    {
        $this->context->controller->addJs($this->_path.'views/js/admin/admin.js');
        $this->context->controller->addCss($this->_path.'views/css/admin/admin.css');
    }

    /**
     * @param int $orderId
     * @param string $status
     * @return int|false
     */
    protected function setOrderState($orderId, $status)
    {
        $order = new Order((int)$orderId);
        if (Validate::isLoadedObject($order)) {
            $orderState = 0;

            if ($status == 'holded') {
                $orderState = (int)Configuration::getGlobalValue('NOVAPAY_OS_WAITING_FOR_CAPTURE');
            } elseif ($status == 'voided') {
                $orderState = (int)Configuration::getGlobalValue('NOVAPAY_OS_PAYMENT_CANCELED');
            } elseif ($status == 'paid') {
                $orderState = (int)_PS_OS_PAYMENT_;
            } elseif ($status == 'failed') {
                $orderState = (int)_PS_OS_ERROR_;
            }
            
            if ($orderState !== 0) {
                if ($orderState !== (int)$order->getCurrentState()) {
                    $orderHistory = new OrderHistory();
                    $orderHistory->id_order = $orderId;
                    $orderHistory->changeIdOrderState($orderState, $orderId);
                    $orderHistory->addWithemail();
                }
                
                return $orderState;
            }
        }
        
        return false;
    }

    /**
     * @param string $orderReference
     * @param string $transactionId
     * @return bool
     */
    protected function setTransactionId($orderReference, $transactionId)
    {
        $orderPayments = (new PrestaShopCollection('OrderPayment'))
            ->where('order_reference', '=', $orderReference);

        $orderPayment = $orderPayments->getFirst();
        if (empty($orderPayment) === true) {
            return false;
        }
        
        $payment = new OrderPayment($orderPayment->id);
        $payment->transaction_id = $transactionId;

        return $payment->save();
    }

    /**
     * @param string $sessionId
     * @param string $status
     * @param null|int $orderId
     */
    public function synchronize($sessionId, $status, $orderId = null)
    {
        if ($orderId) {
            $orderState = $this->setOrderState($orderId, $status);

            if ($orderState == _PS_OS_PAYMENT_) {
                $order = new Order((int)$orderId);
                if (Validate::isLoadedObject($order)) {
                    $this->setTransactionId($order->reference, $sessionId);
                }
            }
        }
        
        $session = NovaPaySession::getSessionBySessionId($sessionId);
        if ($session) {
            $session->status = $status;
            $session->update();
        }
    }

    /**
     * @param string $amount
     */
    protected function parseAmount($amount)
    {
        $regexp = "/^([0-9\s]{0,10})((\.|,)[0-9]{0,2})?$/isD";

        if (preg_match($regexp, $amount)) {
            $arrayRegexp = array('#,#isD', '# #isD');
            $arrayReplace = array('.', '');
            $amount = preg_replace($arrayRegexp, $arrayReplace, $amount);

            return Tools::ps_round($amount, 2);
        }
        
        return false;
    }

    /**
     * @param string $status
     * @param float $amount
     * @param int $currencyId
     * @return string
     */
    protected function generateActionsForm($status, $amount, $currencyId)
    {
        $currency = new Currency((int)$currencyId);

        $this->context->smarty->assign(array(
            'base_url' => $this->context->shop->getBaseURL(),
            'status' => $status,
            'default_capture_amount' => Tools::ps_round($amount, 6),
            'currency_sign' => $currency->getSign(),
            'use_custom_capture_amount' => (bool)Tools::getValue('novapay_use_custom_capture_amount'),
            'custom_capture_amount' => Tools::getValue('novapay_custom_capture_amount'),
        ));

        return $this->display(__FILE__, '/views/templates/admin/actions.tpl');
    }

    public function hookDisplayAdminOrderLeft($params)
    {
        $html = '';
        $order = new Order((int)$params['id_order']);
        
        if ($payment = NovaPayPayment::getPaymentByOrderReference($order->reference)) {
            $client = new NovaPayClient(
                $this->configuration->getMerchantId(),
                $this->configuration->getMerchantPrivateKey(),
                $this->configuration->isSandboxMode()
            );

            $response = $client->getSessionStatus($payment->session_id);
            if (!$response || $response->hasErrors()) {
                $html .= $this->displayError($this->l('Failed to get NovaPay session status'));
            } else {
                $status = $response->getValue('status');

                if (Tools::isSubmit('submit_novapay_synchronize')) {
                    $this->synchronize($payment->session_id, $status, $order->id);
                    Tools::redirectAdmin($_SERVER['REQUEST_URI']);
                } elseif (Tools::isSubmit('submit_novapay_cancel')) {
                    $response = $client->cancelPayment($payment->session_id);
                    if ($response && !$response->hasErrors()) {
                        Tools::redirectAdmin($_SERVER['REQUEST_URI']);
                    }

                    $html .= $this->displayError($this->l('Failed to cancel the payment'));
                } elseif (($status == 'holded') && Tools::isSubmit('submit_novapay_capture')) {
                    $amount = Tools::isSubmit('novapay_use_custom_capture_amount') ?
                        Tools::getValue('novapay_custom_capture_amount') :
                        Tools::getValue('novapay_default_capture_amount');

                    if (($amount = $this->parseAmount($amount)) && Validate::isFloat($amount)) {
                        $amount = Tools::ps_round($amount, 6);
                        $paymentAmount = Tools::ps_round($payment->amount, 6);
                        
                        if (($amount <= Tools::ps_round(0, 6)) || ($amount > $paymentAmount)) {
                            $html .= $this->displayError($this->l('Invalid amount'));
                        } else {
                            $response = $client->doPaymentCapture($payment->session_id, $amount);
                            if ($response && !$response->hasErrors()) {
                                Tools::redirectAdmin($_SERVER['REQUEST_URI']);
                            }
                            
                            $html .= $this->displayError($this->l('Failed to capture the amount'));
                        }
                    }
                }
                
                $html .= $this->generateActionsForm(
                    $status,
                    $payment->amount,
                    $order->id_currency
                );
            }
        }
        
        return $html;
    }
    
    public function hookHeader($params)
    {
        if ($this->isPs17()) {
            $this->context->controller->registerJavascript(
                'modules-novapay',
                'modules/'.$this->name.'/views/js/front/novapay.js',
                array('position' => 'bottom', 'priority' => 150)
            );
    
            $this->context->controller->registerStylesheet(
                'modules-novapay',
                'modules/'.$this->name.'/views/css/front/novapay.css',
                array('media' => 'all', 'priority' => 150)
            );
    
            Media::addJsDef(array(
                'novapay_loader_html' => $this->context->smarty->fetch(
                    'module:novapay/views/templates/front/loader.tpl'
                )
            ));
        } else {
            $this->context->controller->addCss(
                $this->_path.'views/css/front/novapay-16.css'
            );
        }
    }
    
    /**
     * @return bool
     */
    protected function merchantIsValid()
    {
        return $this->configuration->getMerchantId()
            && $this->configuration->getMerchantPrivateKey()
            && $this->configuration->isConnectionChecked();
    }
    
    /**
     * @return bool
     */
    public function checkCurrency($cart)
    {
        $moduleCurrencies = $this->getCurrency($cart->id_currency);
        if (is_array($moduleCurrencies)) {
            foreach ($moduleCurrencies as $moduleCurrency) {
                if ($cart->id_currency == $moduleCurrency['id_currency']) {
                    return true;
                }
            }
        }
        
        return false;
    }

    public function hookPayment($params)
    {
        if (!$this->active) {
            return false;
        }

        if (!$this->merchantIsValid()) {
            return false;
        }

        if (!$this->checkCurrency($params['cart'])) {
            return;
        }
        
        return $this->display(__FILE__, '/views/templates/hook/payment.tpl');
    }

    protected function generatePaymentOptionForm()
    {
        $invoiceAddress = new Address(
            $this->context->cart->id_address_invoice,
            $this->context->language->id
        );

        $this->context->smarty->assign(array(
            'client_first_name' => $invoiceAddress->firstname,
            'client_last_name' => $invoiceAddress->lastname,
            'client_phone' => $invoiceAddress->phone ? $invoiceAddress->phone : $invoiceAddress->phone_mobile,
            'novapay_form_action' => $this->context->link->getModuleLink(
                $this->name,
                'Session'
            ),
        ));

        return $this->context->smarty->fetch(
            'module:novapay/views/templates/hook/payment-option-form.tpl'
        );
    }
    
    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return false;
        }

        if (!$this->merchantIsValid()) {
            return false;
        }

        if (!$this->checkCurrency($params['cart'])) {
            return;
        }
        
        $option = (new PrestaShop\PrestaShop\Core\Payment\PaymentOption())
            ->setModuleName($this->name)
            ->setCallToActionText($this->l('NovaPay'))
            ->setForm($this->generatePaymentOptionForm())
            ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_.$this->name.'/views/img/payment-option-logo.png'));
            
        return array($option);
    }

    public function hookOrderConfirmation($params)
    {
        if (!$this->active) {
            return;
        }

        if ($this->isPs17()) {
            $order = $params['order'];
        } else {
            $order = $params['objOrder'];
        }

        /** @var Order $order */
        if ($order->module !== $this->name) {
            return false;
        }

        if (!$order->valid) {
            $this->context->smarty->assign('status', 'pending');
        } else {
            $this->context->smarty->assign(array(
                'status' => 'ok',
                'id_order' => $order->id,
                'isPs17' => $this->isPs17(),
            ));
        }

        return $this->display(__FILE__, '/views/templates/hook/order-confirmation.tpl');
    }
}
