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
     * @return NovaPayPayment
     */
    protected function createPayment($merchantId, $sessionId)
    {
        $collection = new NovaPayProductCollection();
        
        foreach ($this->context->cart->getProducts() as $item) {
            $product = new NovaPayProduct();
            $product->setDescription($item['name']);
            $product->setCount($item['cart_quantity']);
            $product->setPrice($item['total_wt']);

            $collection->addProduct($product);
        }
        
        $payment = new NovaPayPayment();
        $payment->merchant_id = $merchantId;
        $payment->session_id = $sessionId;
        $payment->external_id = (int)$this->module->currentOrder;
        $payment->amount = (float)$this->context->cart->getOrderTotal(true, Cart::BOTH);
        $payment->products = $collection->getJson();
        $payment->use_hold = $this->module->configuration->isTwoStepPayment();

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

        $client = new NovaPayClient(
            $merchantId,
            $this->module->configuration->getMerchantPrivateKey(),
            $this->module->configuration->isSandboxMode()
        );
        
        $response = $client->createPayment($payment);
        if (!$response || $response->hasErrors() || !$response->getValue('url')) {
            throw new PrestaShopException('Failed to create payment.');
        }

        if (!$payment->add()) {
            throw new PrestaShopException('Failed to save payment.');
        }

        Tools::redirect($response->getValue('url'));
    }
}
