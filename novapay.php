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
     * @var int
     */
    const SEARCH_RESULT_MAX_ITEMS_COUNT = 10;

    /**
     * @var int
     */
    const SHOP_DIMENSION_UNIT_ID_METER = 1;

    /**
     * @var int
     */
    const SHOP_DIMENSION_UNIT_ID_CENTIMETER = 2;

    /**
     * @var int
     */
    const SHOP_DIMENSION_UNIT_ID_MILLIMETER = 3;
    
    /**
     * @var int
     */
    const SHOP_WEIGHT_UNIT_ID_KILOGRAM = 1;

    /**
     * @var int
     */
    const SHOP_WEIGHT_UNIT_ID_GRAM = 2;
    
    /**
     * @var float
     */
    const DELIVERY_MIN_VOLUME_WEIGHT = 0.0004;

    /**
     * @var float
     */
    const DELIVERY_MIN_WEIGHT = 0.1;

    /**
     * @var NovaPay\PrestaShop\Configuration\Configuration
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
        $this->version = '2.0.1';
        $this->author = 'NovaPay';
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('NovaPay');
        $this->description = $this->l('Accept payments with NovaPay.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall this module?');
        $this->ps_versions_compliancy = array('min' => '1.6.1', 'max' => _PS_VERSION_);
        $this->configuration = new NovaPay\PrestaShop\Configuration\Configuration();
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
    public function registerHooks()
    {
        $hookList = array(
            'actionAdminControllerSetMedia',
            'displayAdminOrderLeft',
            'header',
            'orderConfirmation',
            'actionCarrierUpdate',
        );

        if ($this->isPs17()) {
            $hookList[] = 'paymentOptions';
            $hookList[] = 'displayCarrierExtraContent';
        } else {
            $hookList[] = 'payment';
            $hookList[] = 'displayCarrierList';
        }
        
        return $this->registerHook($hookList);
    }
    
    public function install()
    {
        return parent::install() &&
            $this->configuration->initialize() &&
            (new NovaPay\PrestaShop\Manager\CarrierManager($this->configuration))->createCarrier() &&
            (new NovaPay\PrestaShop\Manager\OrderStateManager())->createOrderStates() &&
            (new NovaPay\PrestaShop\Manager\TableManager())->createTables() &&
            $this->registerHooks();
    }

    public function uninstall()
    {
        return (new NovaPay\PrestaShop\Manager\TableManager())->dropTables() &&
            (new NovaPay\PrestaShop\Manager\CarrierManager($this->configuration))->deleteCarrier() &&
            $this->configuration->delete() &&
            parent::uninstall();
    }

    /**
     * @param mixed $value
     *
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

        $shopDimensionUnitId = (int)Tools::getValue('shop_dimension_unit_id');
        if (!$shopDimensionUnitId) {
            $this->errors[] = $this->l('Invalid shop dimension unit');
        }

        $shopWeightUnitId = (int)Tools::getValue('shop_weight_unit_id');
        if (!$shopWeightUnitId) {
            $this->errors[] = $this->l('Invalid shop weight unit');
        }

        return !count($this->errors);
    }

    /**
     * @return NovaPay\Client
     */
    public function getClient()
    {
        return new NovaPay\Client(
            $this->configuration->getMerchantId(),
            $this->configuration->getMerchantPrivateKey(),
            $this->configuration->getMerchantPrivateKeyPassword(),
            $this->configuration->isSandboxMode()
        );
    }

    /**
     * @param bool $active
     */
    protected function setCarrierActive($active)
    {
        $carrier = new Carrier($this->configuration->getCarrierId());
        if (Validate::isLoadedObject($carrier) && ((bool)$carrier->active != (bool)$active)) {
            $carrier->active = (bool)$active;
            $carrier->save();
        }
    }

    /**
     * @param array $extraParams
     *
     * @return string
     */
    protected function getCurrentIndex(array $extraParams = array())
    {
        $params = array(
            'configure' => $this->name,
            'tab_module' => $this->tab,
            'module_name' => $this->name,
        );

        if ($extraParams) {
            $params = array_merge($params, $extraParams);
        }

        return $this->context->link->getAdminLink('AdminModules', false).'&'.http_build_query($params);
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
        Tools::redirectAdmin($this->getCurrentIndex(array(
            'conf' => 6,
            'token' => $this->getToken(),
        )));
    }

    protected function resetMerchantPrivateKeyPassword()
    {
        $this->configuration->setMerchantPrivateKeyPassword('');
        $this->redirectWithConfirmation();
    }

    protected function saveSettings()
    {
        if (!$this->validateSettingsFormFields()) {
            return;
        }

        $this->configuration
            ->setConnectionChecked(false)
            ->setMerchantId(trim(Tools::getValue('merchant_id')))
            ->setServerPublicKey(trim(Tools::getValue('server_public_key')))
            ->setTwoStepPayment(Tools::getValue('two_step_payment'))
            ->setShopDimensionUnitId(Tools::getValue('shop_dimension_unit_id'))
            ->setShopWeightUnitId(Tools::getValue('shop_weight_unit_id'))
            ->setSandboxMode(Tools::getValue('sandbox_mode'));

        $merchantPrivateKey = Tools::getValue('merchant_private_key');
        if ($merchantPrivateKey) {
            $this->configuration->setMerchantPrivateKey(trim($merchantPrivateKey));
        }

        $merchantPrivateKeyPassword = Tools::getValue('merchant_private_key_password');
        if ($merchantPrivateKeyPassword) {
            $this->configuration->setMerchantPrivateKeyPassword($merchantPrivateKeyPassword);
        }
        
        $this->configuration->setConnectionChecked($this->getClient()->checkConnection());
        $this->setCarrierActive(Tools::getValue('enable_carrier'));
        
        $this->redirectWithConfirmation();
    }

    /**
     * @return array
     */
    protected function getShopDimensionUnitIdOptions()
    {
        return array(
            array(
                'id' => 0,
                'name' => $this->l('-- please choose --'),
            ),
            array(
                'id' => self::SHOP_DIMENSION_UNIT_ID_METER,
                'name' => $this->l('Meter'),
            ),
            array(
                'id' => self::SHOP_DIMENSION_UNIT_ID_CENTIMETER,
                'name' => $this->l('Centimeter'),
            ),
            array(
                'id' => self::SHOP_DIMENSION_UNIT_ID_MILLIMETER,
                'name' => $this->l('Millimeter'),
            ),
        );
    }

    /**
     * @return array
     */
    protected function getShopWeightUnitIdOptions()
    {
        return array(
            array(
                'id' => 0,
                'name' => $this->l('-- please choose --'),
            ),
            array(
                'id' => self::SHOP_WEIGHT_UNIT_ID_KILOGRAM,
                'name' => $this->l('Kilogram'),
            ),
            array(
                'id' => self::SHOP_WEIGHT_UNIT_ID_GRAM,
                'name' => $this->l('Gram'),
            ),
        );
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
     *
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
        $helper->currentIndex = $this->getCurrentIndex();
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
            'shop_dimension_unit_id' => Tools::getValue(
                'shop_dimension_unit_id',
                $this->configuration->getShopDimensionUnitId()
            ),
            'shop_weight_unit_id' => Tools::getValue(
                'shop_weight_unit_id',
                $this->configuration->getShopWeightUnitId()
            ),
            'enable_carrier' => Tools::getValue(
                'enable_carrier',
                (bool)((new Carrier($this->configuration->getCarrierId()))->active)
            ),
            'sandbox_mode' => Tools::getValue(
                'sandbox_mode',
                $this->configuration->isSandboxMode()
            ),
        );
    }
    
    /**
     * @return string
     */
    protected function generateSettingsForm()
    {
        $input = array(
            array(
                'type' => 'text',
                'label' => $this->l('Merchant ID'),
                'name' => 'merchant_id',
                'required' => true,
            ),
        );
        
        if ($merchantPrivateKey = $this->configuration->getMerchantPrivateKey()) {
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
            'type' => 'password',
            'label' => $this->l('Merchant private key password'),
            'name' => 'merchant_private_key_password',
            'class' => 'fixed-width-lg',
        );

        $input[] = array(
            'type' => 'html',
            'name' => 'reset_merchant_private_key_password',
            'html_content' => '<button type="submit" id="reset_merchant_private_key_password" class="btn btn-default" name="reset_merchant_private_key_password" value="1">'.$this->l('Reset password').'</button>',
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
            'type' => 'select',
            'label' => $this->l('Shop dimension unit'),
            'name' => 'shop_dimension_unit_id',
            'options' => array(
                'query' => $this->getShopDimensionUnitIdOptions(),
                'id' => 'id',
                'name' => 'name',
            ),
            'required' => true,
        );

        $input[] = array(
            'type' => 'select',
            'label' => $this->l('Shop weight unit'),
            'name' => 'shop_weight_unit_id',
            'options' => array(
                'query' => $this->getShopWeightUnitIdOptions(),
                'id' => 'id',
                'name' => 'name',
            ),
            'required' => true,
        );

        $input[] = array(
            'type' => 'switch',
            'label' => $this->l('Enable carrier'),
            'name' => 'enable_carrier',
            'is_bool' => true,
            'values' => array(
                array(
                    'id' => 'enable_carrier_on',
                    'value' => 1
                ),
                array(
                    'id' => 'enable_carrier_off',
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
            'submit_settings',
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
        if (Tools::isSubmit('reset_merchant_private_key_password')) {
            $this->resetMerchantPrivateKeyPassword();
        } elseif (Tools::isSubmit('submit_settings')) {
            $this->saveSettings();
        }
        
        $html = '';

        if ($this->configuration->getMerchantId() &&
            $this->configuration->getMerchantPrivateKey() &&
            !$this->configuration->isConnectionChecked()) {
            $html .= $this->displayWarning($this->l('Failed to connect to NovaPay. The module is not available at the front office. Please make sure that the merchant details are correct.'));
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
     * @param string $status
     *
     * @return int
     */
    protected function getOrderStateBySessionStatus($status)
    {
        switch ($status) {
            case 'holded':
                return (int)Configuration::getGlobalValue('NOVAPAY_OS_WAITING_FOR_PAYMENT_CONFIRMATION');
            case 'hold_confirmed':
                return (int)Configuration::getGlobalValue('NOVAPAY_OS_WAITING_FOR_PAYMENT_CONFIRMATION_AFTER_DELIVERY');
            case 'voided':
                return (int)Configuration::getGlobalValue('NOVAPAY_OS_PAYMENT_CANCELED');
            case 'paid':
                return (int)_PS_OS_PAYMENT_;
            case 'failed':
                return (int)_PS_OS_ERROR_;
            default:
                return 0;
        }
    }

    /**
     * @param string $orderReference
     * @param string $transactionId
     *
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
     * @param Order $order
     * @param int $state
     * @param null|string $transactionId
     */
    public function setOrderState(Order $order, $state, $transactionId = null)
    {
        if (!Validate::isLoadedObject($order) || !($state = (int)$state)) {
            return;
        }

        if ($state != (int)$order->getCurrentState()) {
            $orderHistory = new OrderHistory();
            $orderHistory->id_order = $order->id;
            $orderHistory->changeIdOrderState($state, $order->id);
            $orderHistory->addWithemail();
        }
        
        if (($state == (int)_PS_OS_PAYMENT_) && $transactionId) {
            $this->setTransactionId($order->reference, (string)$transactionId);
        }
    }

    /**
     * @param string $sessionId
     * @param string $status
     * @param null|int $orderId
     */
    public function synchronize($sessionId, $status, $orderId = null)
    {
        $this->setOrderState(
            new Order((int)$orderId),
            $this->getOrderStateBySessionStatus($status),
            $sessionId
        );
        
        $session = NovaPaySession::getSessionBySessionId($sessionId);
        if ($session && ($session->status != $status)) {
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
     * @param int $orderId
     *
     * @return OrderCarrier|null
     */
    protected function getOrderCarrier($orderId)
    {
        $query = (new DbQuery())
            ->select('oc.id_order_carrier')
            ->from('order_carrier', 'oc')
            ->where('oc.id_order = '.(int)$orderId);
        
        if ($id = Db::getInstance()->getValue($query->build())) {
            $orderCarrier = new OrderCarrier((int)$id);
            if (Validate::isLoadedObject($orderCarrier)) {
                return $orderCarrier;
            }
        }
        
        return null;
    }

    /**
     * @param int $orderId
     * @param string $trackingNumber
     *
     * @return bool
     */
    protected function setTrackingNumber($orderId, $trackingNumber)
    {
        if ($orderCarrier = $this->getOrderCarrier($orderId)) {
            $orderCarrier->tracking_number = $trackingNumber;
            
            return $orderCarrier->update();
        }

        return false;
    }

    /**
     * @param int $orderId
     *
     * @return mixed
     */
    protected function getTrackingNumber($orderId)
    {
        return ($orderCarrier = $this->getOrderCarrier($orderId)) ? $orderCarrier->tracking_number : null;
    }

    /**
     * @param string $fileContent
     * @param string $fileName
     */
    protected function submitExpressWaybill($fileContent, $fileName)
    {
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Description: File Transfer');
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="'.$fileName.'.pdf"');
        header('Content-Length: '.Tools::strlen($fileContent));
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: private, must-revalidate, post-check=0, pre-check=0, max-age=1');
        header('Pragma: public');
        header('Expires: 0');
        
        echo $fileContent;

        exit;
    }

    /**
     * @param string $status
     * @param string $localStatus
     * @param float $amount
     * @param int $currencyId
     * @param bool $delivery
     *
     * @return string
     */
    protected function generateActionsForm($status, $localStatus, $amount, $currencyId, $delivery)
    {
        $currency = new Currency((int)$currencyId);

        $this->context->smarty->assign(array(
            'base_url' => $this->context->shop->getBaseURL(),
            'status' => (string)$status,
            'local_status' => (string)$localStatus,
            'default_capture_amount' => Tools::ps_round((float)$amount, 6),
            'currency_sign' => $currency->getSign(),
            'use_custom_capture_amount' => (bool)Tools::getValue('novapay_use_custom_capture_amount'),
            'custom_capture_amount' => Tools::getValue('novapay_custom_capture_amount'),
            'delivery' => (bool)$delivery,
            'preview_express_waybill_link' => $_SERVER['REQUEST_URI'].'&novapay_preview_express_waybill',
        ));

        return $this->display(__FILE__, '/views/templates/admin/actions.tpl');
    }

    public function hookDisplayAdminOrderLeft($params)
    {
        $html = '';
        $order = new Order((int)$params['id_order']);
        
        if ($payment = NovaPayPayment::getPaymentByOrderReference($order->reference)) {
            $session = NovaPaySession::getSessionBySessionId($payment->session_id);
            if (!$session || !$session->status) {
                $html .= $this->displayError($this->l('Failed to get NovaPay session local status'));
            } else {
                $client = $this->getClient();
                $response = $client->getSessionStatus($payment->session_id);
                if (!$response || $response->hasErrors() || !$response->getValue('status')) {
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
                    } elseif (Tools::isSubmit('submit_novapay_capture')) {
                        if ($status != 'holded') {
                            $html .= $this->displayError($this->l('Invalid session status'));
                        } else {
                            $useCustomCaptureAmount = Tools::isSubmit('novapay_use_custom_capture_amount');
                            $amount = $this->parseAmount(
                                $useCustomCaptureAmount ?
                                    Tools::getValue('novapay_custom_capture_amount') :
                                    Tools::getValue('novapay_default_capture_amount')
                            );

                            if (!$amount || !Validate::isFloat($amount)) {
                                $html .= $this->displayError($this->l('Invalid amount'));
                            } else {
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
                    } elseif (Tools::isSubmit('submit_novapay_confirm_delivery')) {
                        if ($status != 'holded') {
                            $html .= $this->displayError($this->l('Invalid session status'));
                        } else {
                            $response = $client->confirmDeliveryHold($payment->session_id);
                            if ($response && !$response->hasErrors() && $response->getValue('express_waybill')) {
                                $this->setTrackingNumber($order->id, $response->getValue('express_waybill'));
                                Tools::redirectAdmin($_SERVER['REQUEST_URI']);
                            }

                            $html .= $this->displayError($this->l('Failed to create express waybill'));
                        }
                    } elseif (Tools::isSubmit('novapay_preview_express_waybill')) {
                        if ($status != 'hold_confirmed') {
                            $html .= $this->displayError($this->l('Invalid session status'));
                        } else {
                            $response = $client->printExpressWaybill($payment->session_id);
                            if ($response && !$response->hasErrors() && $response->getValue('data')) {
                                $trackingNumber = $this->getTrackingNumber($order->id);

                                $this->submitExpressWaybill(
                                    $response->getValue('data'),
                                    $trackingNumber ? $trackingNumber : $order->id
                                );
                            }

                            $html .= $this->displayError($this->l('Failed to get express waybill'));
                        }
                    }
                    
                    $html .= $this->generateActionsForm(
                        $status,
                        $session->status,
                        $payment->amount,
                        $order->id_currency,
                        $payment->delivery
                    );
                }
            }
        }
        
        return $html;
    }
    
    public function hookHeader($params)
    {
        $this->context->controller->addJqueryPlugin('chosen');

        if ($this->isPs17()) {
            $this->context->controller->registerJavascript(
                'modules-novapay-easy-autocomplete',
                'modules/'.$this->name.'/views/js/front/jquery.easy-autocomplete.js',
                array('position' => 'bottom', 'priority' => 150)
            );

            $this->context->controller->registerJavascript(
                'modules-novapay',
                'modules/'.$this->name.'/views/js/front/novapay.js',
                array('position' => 'bottom', 'priority' => 150)
            );

            $this->context->controller->registerJavascript(
                'modules-novapay-1.7',
                'modules/'.$this->name.'/views/js/front/novapay-1.7.js',
                array('position' => 'bottom', 'priority' => 150)
            );

            $this->context->controller->registerStylesheet(
                'modules-novapay-easy-autocomplete',
                'modules/'.$this->name.'/views/css/front/easy-autocomplete.css',
                array('media' => 'all', 'priority' => 150)
            );

            $this->context->controller->registerStylesheet(
                'modules-novapay-chosen',
                'modules/'.$this->name.'/views/css/front/chosen.css',
                array('media' => 'all', 'priority' => 150)
            );
    
            $this->context->controller->registerStylesheet(
                'modules-novapay',
                'modules/'.$this->name.'/views/css/front/novapay.css',
                array('media' => 'all', 'priority' => 150)
            );

            $this->context->controller->registerStylesheet(
                'modules-novapay-1.7',
                'modules/'.$this->name.'/views/css/front/novapay-1.7.css',
                array('media' => 'all', 'priority' => 150)
            );
        } else {
            $this->context->controller->addJs(array(
                $this->_path.'views/js/front/jquery.easy-autocomplete.js',
                $this->_path.'views/js/front/novapay.js',
                $this->_path.'views/js/front/novapay-1.6.js',
            ));

            $this->context->controller->addCss(array(
                $this->_path.'views/css/front/easy-autocomplete.css',
                $this->_path.'views/css/front/chosen.css',
                $this->_path.'views/css/front/novapay.css',
                $this->_path.'views/css/front/novapay-1.6.css',
            ));
        }

        Media::addJsDef(array(
            'novapay_loader_html' => preg_replace(
                '/[\n\r]/',
                '',
                $this->display(__FILE__, '/views/templates/front/loader.tpl')
            )
        ));
    }
    
    /**
     * @return bool
     */
    public function merchantIsValid()
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

    /**
     * @return bool
     */
    public function isSafeDeal()
    {
        return (int)$this->context->cart->id_carrier == $this->configuration->getCarrierId();
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
            'novapay_form_action' => $this->context->link->getModuleLink($this->name, 'Session'),
            'safe_deal' => $this->isSafeDeal(),
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

        if ((int)$order->getCurrentState() == (int)_PS_OS_ERROR_) {
            $this->context->smarty->assign('status', 'error');
        } elseif (!$order->valid) {
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

    /**
     * @param null|NovaPayCartWarehouse $cartWarehouse
     *
     * @return NovaPay\Delivery|null
     */
    public function getDeliveryObject($cartWarehouse = null)
    {
        if (!$cartWarehouse) {
            $cartWarehouse = NovaPayCartWarehouse::getCartWarehouseByCartId($this->context->cart->id);
        }
        
        if ($cartWarehouse &&
            $cartWarehouse->reference &&
            $cartWarehouse->city_reference) {
            $volumeWeight = 0;
            $weight = 0;
    
            foreach ($this->context->cart->getProducts() as $product) {
                $quantity = (int)$product['cart_quantity'];
                $volumeWeight += (float)$product['width'] * (float)$product['height'] *
                    (float)$product['depth'] * $quantity;
                $weight += (float)$product['weight'] * $quantity;
            }

            $dimensionUnitId = $this->configuration->getShopDimensionUnitId();
            if ($dimensionUnitId == self::SHOP_DIMENSION_UNIT_ID_CENTIMETER) {
                $volumeWeight /= pow(100, 3);
            } elseif ($dimensionUnitId == self::SHOP_DIMENSION_UNIT_ID_MILLIMETER) {
                $volumeWeight /= pow(1000, 3);
            } elseif ($dimensionUnitId != self::SHOP_DIMENSION_UNIT_ID_METER) {
                $volumeWeight = 0;
            }

            if ($volumeWeight < self::DELIVERY_MIN_VOLUME_WEIGHT) {
                $volumeWeight = self::DELIVERY_MIN_VOLUME_WEIGHT;
            }

            $weightUnitId = $this->configuration->getShopWeightUnitId();
            if ($weightUnitId == self::SHOP_WEIGHT_UNIT_ID_GRAM) {
                $weight /= pow(1000, 3);
            } elseif ($weightUnitId != self::SHOP_WEIGHT_UNIT_ID_KILOGRAM) {
                $weight = 0;
            }

            if ($weight < self::DELIVERY_MIN_WEIGHT) {
                $weight = self::DELIVERY_MIN_WEIGHT;
            }

            return (new NovaPay\Delivery())
                ->setAmount((float)$this->context->cart->getOrderTotal(true, Cart::ONLY_PRODUCTS))
                ->setVolumeWeight($volumeWeight)
                ->setWeight($weight)
                ->setRecipientCityReference($cartWarehouse->city_reference)
                ->setRecipientWarehouseReference($cartWarehouse->reference);
        }

        return null;
    }

    /**
     * @return false|float
     */
    public function getDeliveryPrice()
    {
        if (!$this->active || !$this->merchantIsValid()) {
            return false;
        }

        $cartWarehouse = NovaPayCartWarehouse::getCartWarehouseByCartId($this->context->cart->id);
        if ($cartWarehouse) {
            $cartWarehouse->delivery_price = (float)$cartWarehouse->delivery_price;
            if (!$cartWarehouse->delivery_price &&
                ($delivery = $this->getDeliveryObject($cartWarehouse)) &&
                ($response = $this->getClient()->getDeliveryPrice($delivery)) &&
                !$response->hasErrors()) {
                $cartWarehouse->delivery_price = (float)$response->getValue('delivery_price');
                $cartWarehouse->save();
            }
            
            return $cartWarehouse->delivery_price;
        }
        
        return 0.0;
    }
    
    public function getOrderShippingCost($params)
    {
        return $this->getDeliveryPrice();
    }

    public function getOrderShippingCostExternal($params)
    {
        return $this->getDeliveryPrice();
    }

    /**
     * @param string $reference
     *
     * @return array|null
     */
    public function getCity($reference)
    {
        $response = $this->getClient()->getDeliveryInfo(
            (new NovaPay\DeliveryInfo\Request())
                ->setModelName('Address')
                ->setMethodName('getCities')
                ->setMethodProperty('Ref', $reference)
        );

        if ($response && $response->isSuccess()) {
            $data = $response->getData();
            if (isset($data[0])) {
                $descriptionKey = 'Description';
                $settlementTypeKey = 'SettlementTypeDescription';

                if ($this->context->language->iso_code === 'ru') {
                    $descriptionKey .= 'Ru';
                    $settlementTypeKey .= 'Ru';
                }
                
                return array(
                    'id' => $data[0]['CityID'],
                    'description' => $data[0][$descriptionKey],
                    'reference' => $data[0]['Ref'],
                    'type' => $data[0][$settlementTypeKey],
                );
            }
        }

        return null;
    }

    /**
     * @param string $searchString
     *
     * @return array
     */
    public function getCities($searchString)
    {
        $cities = array();
        $response = $this->getClient()->getDeliveryInfo(
            (new NovaPay\DeliveryInfo\Request())
                ->setModelName('Address')
                ->setMethodName('getCities')
                ->setMethodProperty('Limit', self::SEARCH_RESULT_MAX_ITEMS_COUNT)
                ->setMethodProperty('FindByString', $searchString)
        );

        if ($response && $response->isSuccess()) {
            $descriptionKey = 'Description';
            $settlementTypeKey = 'SettlementTypeDescription';

            if ($this->context->language->iso_code === 'ru') {
                $descriptionKey .= 'Ru';
                $settlementTypeKey .= 'Ru';
            }
            
            foreach ($response->getData() as $city) {
                $cities[] = array(
                    'id' => $city['CityID'],
                    'description' => $city[$descriptionKey],
                    'reference' => $city['Ref'],
                    'type' => $city[$settlementTypeKey],
                );
            }
        }

        return $cities;
    }

    /**
     * @param string $cityReference
     *
     * @return array
     */
    public function getWarehouses($cityReference)
    {
        $page = 1;
        $request = (new NovaPay\DeliveryInfo\Request())
            ->setModelName('AddressGeneral')
            ->setMethodName('getWarehouses')
            ->setMethodProperty('Page', $page);

        if ($cityReference) {
            $request->setMethodProperty('CityRef', $cityReference);
        }
        
        $warehouses = array();
        $descriptionKey = 'Description';
        $cityDescriptionKey = 'CityDescription';

        if ($this->context->language->iso_code === 'ru') {
            $descriptionKey .= 'Ru';
            $cityDescriptionKey .= 'Ru';
        }
        
        $client = $this->getClient();
        
        while (($response = $client->getDeliveryInfo($request)) && $response->isSuccess()) {
            if (count($response->getData()) == 0) {
                break;
            }

            foreach ($response->getData() as $warehouse) {
                $warehouses[] = array(
                    'reference' => $warehouse['Ref'],
                    'description' => $warehouse[$descriptionKey],
                    'number' => $warehouse['Number'],
                    'cityReference' => $warehouse['CityRef'],
                    'cityDescription' => $warehouse[$cityDescriptionKey],
                );
            }
            
            $request->setMethodProperty('Page', ++$page);
        }
        
        return $warehouses;
    }

    public function hookDisplayCarrierExtraContent($params)
    {
        $cityReference = '';
        $cityDescription = '';
        $warehouseReference = '';
        $warehouses = array();
        $cartWarehouse = NovaPayCartWarehouse::getCartWarehouseByCartId($this->context->cart->id);
        if ($cartWarehouse) {
            if ($cartWarehouse->city_reference) {
                if ($city = $this->getCity($cartWarehouse->city_reference)) {
                    $cityReference = $city['reference'];
                    $cityDescription = $city['description'];
                    $warehouses = $this->getWarehouses($cartWarehouse->city_reference);

                    if ($cartWarehouse->reference) {
                        $warehouseReferenceIsValid = false;

                        foreach ($warehouses as $warehouse) {
                            if ($cartWarehouse->reference === $warehouse['reference']) {
                                $warehouseReferenceIsValid = true;
                                break;
                            }
                        }

                        if ($warehouseReferenceIsValid) {
                            $warehouseReference = $cartWarehouse->reference;
                        } else {
                            $cartWarehouse->resetReference();
                        }
                    }
                } else {
                    $cartWarehouse->resetReferences();
                }
            } elseif ($cartWarehouse->reference) {
                $cartWarehouse->resetReference();
            }
        }
        
        $this->context->smarty->assign(array(
            'client_city_reference' => $cityReference,
            'client_city_description' => $cityDescription,
            'client_warehouse_reference' => $warehouseReference,
            'warehouses' => $warehouses,
            'carrier_id' => $this->configuration->getCarrierId(),
            'get_cities_url' => $this->context->link->getModuleLink($this->name, 'Carrier', array(
                'ajax' => true,
                'action' => 'getCities',
            )),
            'get_warehouses_url' => $this->context->link->getModuleLink($this->name, 'Carrier', array(
                'ajax' => true,
                'action' => 'getWarehouses',
            )),
            'set_warehouse_url' => $this->context->link->getModuleLink($this->name, 'Carrier', array(
                'ajax' => true,
                'action' => 'setWarehouse',
            )),
        ));

        return $this->display(__FILE__, '/views/templates/hook/delivery-option-extra-content.tpl');
    }

    public function hookDisplayCarrierList($params)
    {
        if ($this->isSafeDeal()) {
            return $this->hookDisplayCarrierExtraContent($params);
        }
        
        return null;
    }

    public function hookActionCarrierUpdate($params)
    {
        /** @var Carrier $carrier */
        $carrier = $params['carrier'];

        if ((int)$carrier->id_reference == $this->configuration->getCarrierIdReference()) {
            $this->configuration->setCarrierId($carrier->id);
        }
    }
}
