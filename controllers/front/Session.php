<?php
/**
 * License
 * @author mnemonic88uk
 * @copyright 2020 mnemonic88uk
 * @license https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

class NovaPaySessionModuleFrontController extends NovaPayFrontController
{
    /**
     * @var bool
     */
    public $ssl = true;

    /**
     * @var bool
     */
    public $display_column_left = false;

    /**
     * @return array
     */
    protected function getSafeDealSessionValidationErrors()
    {
        $errors = array();
        $pattern = '/^[А-Яа-яҐґЄєІіЇїЁё\'\- ]+$/u';
        $allowedCharacters = $this->module->l('Allowed characters: letters of the Ukrainian and Russian alphabets, as well as space, hyphen and apostrophe.', 'Session');

        $firstName = trim(Tools::getValue('client_first_name', ''));
        if (!$firstName) {
            $errors[] = $this->module->l('First name is required.', 'Session');
        } elseif (!preg_match($pattern, $firstName)) {
            $errors[] = $this->module->l('First name contains invalid characters.', 'Session').' '.$allowedCharacters;
        }

        $lastName = trim(Tools::getValue('client_last_name', ''));
        if (!$lastName) {
            $errors[] = $this->module->l('Last name is required.', 'Session');
        } elseif (!preg_match($pattern, $lastName)) {
            $errors[] = $this->module->l('Last name contains invalid characters.', 'Session').' '.$allowedCharacters;
        }

        $patronymic = trim(Tools::getValue('client_patronymic', ''));
        if ($patronymic && !preg_match($pattern, $patronymic)) {
            $errors[] = $this->module->l('Patronymic contains invalid characters.', 'Session').' '.$allowedCharacters;
        }
        
        return $errors;
    }

    /**
     * @param string $merchantId
     * @param string $customerSecureKey
     *
     * @return NovaPaySession
     */
    protected function createSession($merchantId, $customerSecureKey)
    {
        $session = new NovaPaySession();
        $session->merchant_id = $merchantId;
        $session->client_first_name = Tools::getValue('client_first_name');
        $session->client_last_name = Tools::getValue('client_last_name');
        $session->client_patronymic = Tools::getValue('client_patronymic');
        $session->client_phone = preg_replace('/[ \(\)\-]/', '', Tools::getValue('client_phone'));

        $session->callback_url = $this->context->link->getModuleLink(
            $this->module->name,
            'Callback'
        );

        $session->success_url = $this->getOrderConfirmationUrl(
            $customerSecureKey,
            array('success' => 1)
        );

        $session->fail_url = $this->getOrderConfirmationUrl(
            $customerSecureKey,
            array('success' => 0)
        );

        return $session;
    }

    /**
     * @param array $data
     */
    protected function displayAjaxData(array $data)
    {
        die(json_encode($data));
    }
    
    /**
     * @param string $error
     */
    protected function displayAjaxError($error)
    {
        $this->displayAjaxData(array('error' => $error));
    }

    /**
     * @param NovaPay\Response $response
     *
     * @return array
     */
    protected function getSessionCreationErrors(NovaPay\Response $response)
    {
        $errors = array();

        foreach ($response->getErrors() as $error) {
            if ($error->getCode() == '.client_first_name') {
                $errors['.client_first_name'] = $this->module->l('Invalid first name.', 'Session');
            } elseif ($error->getCode() == '.client_last_name') {
                $errors['.client_last_name'] = $this->module->l('Invalid last name.', 'Session');
            } elseif ($error->getCode() == '.client_patronymic') {
                $errors['.client_patronymic'] = $this->module->l('Invalid patronymic.', 'Session');
            } elseif ($error->getCode() == '.client_phone') {
                $errors['.client_phone'] = $this->module->l('Invalid phone number.', 'Session');
            } else {
                return array($this->module->l('Failed to create session.', 'Session'));
            }
        }

        return array_values($errors);
    }
    
    public function postProcess()
    {
        if (!$this->ajax && Tools::isSubmit('submit_novapay_session_data')) {
            if (!$this->checkIfContextIsValid()) {
                throw new PrestaShopException('The context is not valid.');
            }

            if (!$this->checkIfPaymentOptionIsAvailable()) {
                throw new PrestaShopException('This payment method is not available.');
            }

            $customer = new Customer($this->context->cart->id_customer);
            if (!Validate::isLoadedObject($customer)) {
                $this->redirectToCheckout(array('step' => 1));
            }

            if ($this->module->isSafeDeal() &&
                ($errors = $this->getSafeDealSessionValidationErrors())) {
                $this->context->smarty->assign(array(
                    'session_errors' => $errors
                ));

                return;
            }
            
            $merchantId = $this->module->configuration->getMerchantId();

            $client = new NovaPay\Client(
                $merchantId,
                $this->module->configuration->getMerchantPrivateKey(),
                $this->module->configuration->getMerchantPrivateKeyPassword(),
                $this->module->configuration->isSandboxMode()
            );

            $session = $this->createSession(
                $merchantId,
                $customer->secure_key
            );

            $response = $client->createSession($session);
            if (!$response) {
                throw new PrestaShopException('Invalid response.');
            }
            
            if ($response->hasErrors()) {
                $this->context->smarty->assign(array(
                    'session_errors' => $this->getSessionCreationErrors($response)
                ));

                return;
            }
            
            if (!$response->getValue('id')) {
                throw new PrestaShopException('Invalid session ID.');
            }
            
            $session->session_id = $response->getValue('id');
    
            if (!$session->add()) {
                throw new PrestaShopException('Failed to save session.');
            }

            Tools::redirect($this->context->link->getModuleLink(
                $this->module->name,
                'Validation',
                array('session_id' => $session->session_id)
            ));
        }
    }

    public function initContent()
    {
        parent::initContent();
        
        if (!$this->ajax) {
            $invoiceAddress = new Address(
                $this->context->cart->id_address_invoice,
                $this->context->language->id
            );

            $this->context->smarty->assign(array(
                'nb_products' => $this->context->cart->nbProducts(),
                'client_first_name' => Tools::getValue('client_first_name', $invoiceAddress->firstname),
                'client_last_name' => Tools::getValue('client_last_name', $invoiceAddress->lastname),
                'client_patronymic' => Tools::getValue('client_patronymic', ''),
                'client_phone' => Tools::getValue('client_phone', $invoiceAddress->phone ? $invoiceAddress->phone :
                    $invoiceAddress->phone_mobile),
                'novapay_form_action' => $this->context->link->getModuleLink(
                    $this->module->name,
                    'Session'
                ),
                'safe_deal' => $this->module->isSafeDeal(),
            ));
    
            $this->setTemplate('session.tpl');
        }
    }
    
    public function displayAjax()
    {
        if (!$this->checkIfContextIsValid()) {
            $this->displayAjaxError(
                $this->module->l('The context is not valid.', 'Session')
            );
        }
        
        if (!$this->checkIfPaymentOptionIsAvailable()) {
            $this->displayAjaxError(
                $this->module->l('This payment method is not available.', 'Session')
            );
        }

        $customer = new Customer($this->context->cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            $this->displayAjaxData(array(
                'url' => $this->getCheckoutUrl(array('step' => 1))
            ));
        }

        if ($this->module->isSafeDeal() &&
            ($errors = $this->getSafeDealSessionValidationErrors())) {
            $this->displayAjaxData(array(
                'errors' => $errors
            ));
        }

        $merchantId = $this->module->configuration->getMerchantId();

        $client = new NovaPay\Client(
            $merchantId,
            $this->module->configuration->getMerchantPrivateKey(),
            $this->module->configuration->getMerchantPrivateKeyPassword(),
            $this->module->configuration->isSandboxMode()
        );

        $session = $this->createSession(
            $merchantId,
            $customer->secure_key
        );

        $response = $client->createSession($session);
        if (!$response) {
            $this->displayAjaxError(
                $this->module->l('Invalid response.', 'Session')
            );
        }
        
        if ($response->hasErrors()) {
            $this->displayAjaxData(array(
                'errors' => $this->getSessionCreationErrors($response)
            ));
        }
        
        if (!$response->getValue('id')) {
            $this->displayAjaxError(
                $this->module->l('Invalid session ID.', 'Session')
            );
        }
        
        $session->session_id = $response->getValue('id');

        if (!$session->add()) {
            $this->displayAjaxError(
                $this->module->l('Failed to save session.', 'Session')
            );
        }
        
        $this->displayAjaxData(array(
            'url' => $this->context->link->getModuleLink(
                $this->module->name,
                'Validation',
                array('session_id' => $session->session_id)
            )
        ));
    }
}
