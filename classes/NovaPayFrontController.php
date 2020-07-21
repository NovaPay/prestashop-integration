<?php
/**
 * License
 * @author mnemonic88uk
 * @copyright 2020 mnemonic88uk
 * @license https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

class NovaPayFrontController extends ModuleFrontController
{
    /**
     * @var NovaPay
     */
    public $module;
    
    /**
     * @return bool
     */
    protected function checkIfContextIsValid()
    {
        return ($this->context->cart->id_customer != 0) &&
            ($this->context->cart->id_address_delivery != 0) &&
            ($this->context->cart->id_address_invoice != 0);
    }
    
    /**
     * @return bool
     */
    protected function checkIfPaymentOptionIsAvailable()
    {
        foreach (Module::getPaymentModules() as $module) {
            if (($module['name'] == $this->module->name) && $this->module->active) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array $params
     * @return string
     */
    protected function getCheckoutUrl(array $params = array())
    {
        return $this->context->link->getPageLink(
            'order',
            true,
            $this->context->language->id,
            $params
        );
    }

    /**
     * @param array $params
     */
    protected function redirectToCheckout(array $params = array())
    {
        Tools::redirect($this->getCheckoutUrl($params));
    }
}
