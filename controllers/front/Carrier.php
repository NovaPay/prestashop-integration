<?php
/**
 * License
 * @author mnemonic88uk
 * @copyright 2020 mnemonic88uk
 * @license https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

class NovaPayCarrierModuleFrontController extends NovaPayFrontController
{
    public function displayAjaxGetCities()
    {
        die(json_encode($this->module->getCities(Tools::getValue('search_string'))));
    }

    public function displayAjaxGetWarehouses()
    {
        die(json_encode($this->module->getWarehouses(Tools::getValue('city_reference'))));
    }

    public function displayAjaxSetWarehouse()
    {
        $cartWarehouse = NovaPayCartWarehouse::getCartWarehouseByCartId($this->context->cart->id);
        if (!$cartWarehouse) {
            $cartWarehouse = new NovaPayCartWarehouse();
            $cartWarehouse->id_cart = (int)$this->context->cart->id;
        }

        $cartWarehouse->reference = Tools::getValue('warehouse_reference');
        $cartWarehouse->city_reference = Tools::getValue('city_reference');
        $cartWarehouse->delivery_price = 0.0;

        $cartWarehouse->save();
        
        die(json_encode(array('success' => 1)));
    }
}
