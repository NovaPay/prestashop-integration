<?php
/**
 * License
 * @author mnemonic88uk
 * @copyright 2020 mnemonic88uk
 * @license https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

class NovaPayValidationModuleFrontController extends NovaPayFrontController
{
    /**
     * @param string $merchantId
     * @param string $sessionId
     *
     * @return NovaPayPayment
     */
    protected function createPayment($merchantId, $sessionId)
    {
        $collection = new NovaPay\ProductCollection();
        
        foreach ($this->context->cart->getProducts() as $item) {
            $collection->addProduct(
                (new NovaPay\Product())
                    ->setDescription($item['name'])
                    ->setCount($item['cart_quantity'])
                    ->setPrice($item['total_wt'])
            );
        }
        
        $payment = new NovaPayPayment();
        $payment->merchant_id = $merchantId;
        $payment->session_id = $sessionId;
        $payment->external_id = (int)$this->module->currentOrder;
        
        if ($this->module->isSafeDeal() &&
            ($delivery = $this->module->getDeliveryObject())) {
            $payment->amount = $delivery->getAmount();
            $payment->use_hold = true;
            $payment->delivery = $delivery->getJson(false);
        } else {
            $payment->amount = (float)$this->context->cart->getOrderTotal(true, Cart::BOTH);
            $payment->use_hold = $this->module->configuration->isTwoStepPayment();
            
            if ($deliveryPrice = (float)$this->context->cart->getOrderTotal(true, Cart::ONLY_SHIPPING)) {
                $collection->addProduct(
                    (new NovaPay\Product())
                        ->setDescription($this->module->l('Delivery', 'Validation'))
                        ->setCount(1)
                        ->setPrice($deliveryPrice)
                );
            }
        }

        $payment->products = $collection->getJson();
        
        return $payment;
    }

    public function postProcess()
    {
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

        $sessionId = Tools::getValue('session_id');
        if (!$sessionId) {
            $this->redirectToCheckout();
        }

        $this->module->validateOrder(
            $this->context->cart->id,
            Configuration::getGlobalValue('NOVAPAY_OS_WAITING_FOR_PAYMENT'),
            (float)$this->context->cart->getOrderTotal(true, Cart::BOTH),
            $this->module->displayName,
            null,
            array(),
            $this->context->currency->id,
            false,
            $customer->secure_key
        );
        
        $merchantId = $this->module->configuration->getMerchantId();
        $payment = $this->createPayment($merchantId, $sessionId);

        $client = new NovaPay\Client(
            $merchantId,
            $this->module->configuration->getMerchantPrivateKey(),
            $this->module->configuration->getMerchantPrivateKeyPassword(),
            $this->module->configuration->isSandboxMode()
        );
        
        $response = $client->createPayment($payment);
        if (!$response || $response->hasErrors() || !$response->getValue('url')) {
            $this->module->setOrderState(new Order((int)$this->module->currentOrder), _PS_OS_ERROR_);
            Tools::redirect($this->getOrderConfirmationUrl($customer->secure_key));
        }

        $payment->payment_id = $response->getValue('id');
        $payment->delivery_price = (float)$response->getValue('delivery_price');

        if (!$payment->add()) {
            throw new PrestaShopException('Failed to save payment.');
        }

        Tools::redirect($response->getValue('url'));
    }
}
