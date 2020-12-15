<?php
/**
 * License
 * @author mnemonic88uk
 * @copyright 2020 mnemonic88uk
 * @license https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

class NovaPayCartWarehouse extends ObjectModel
{
    /**
     * @var int
     */
    public $id_cart = null;

    /**
     * @var string
     */
    public $reference = null;

    /**
     * @var string
     */
    public $city_reference = null;

    /**
     * @var float
     */
    public $delivery_price = null;
    
    /**
     * @var array
     */
    public static $definition = array(
        'table' => 'novapay_cart_warehouse',
        'primary' => 'id_novapay_cart_warehouse',
        'fields' => array(
            'id_cart' => array('type' => self::TYPE_INT, 'required' => true, 'size' => 10),
            'reference' => array('type' => self::TYPE_STRING, 'size' => 36),
            'city_reference' => array('type' => self::TYPE_STRING, 'size' => 36),
            'delivery_price' => array('type' => self::TYPE_FLOAT),
        ),
    );

    /**
     * @param int $cartId
     *
     * @return NovaPayCartWarehouse|null
     */
    public static function getCartWarehouseByCartId($cartId)
    {
        $query = (new DbQuery())
            ->select('cart_warehouse.id_novapay_cart_warehouse')
            ->from('novapay_cart_warehouse', 'cart_warehouse')
            ->where('cart_warehouse.id_cart = '.(int)$cartId);

        if (!$id = Db::getInstance()->getValue($query->build())) {
            return null;
        }
        
        $cartWarehouse = new NovaPayCartWarehouse((int)$id);
        if (!Validate::isLoadedObject($cartWarehouse)) {
            return null;
        }

        return $cartWarehouse;
    }

    /**
     * @return bool
     */
    public function resetReference()
    {
        $this->reference = '';
        $this->delivery_price = 0.0;
        
        return $this->save();
    }

    /**
     * @return bool
     */
    public function resetReferences()
    {
        $this->reference = '';
        $this->city_reference = '';
        $this->delivery_price = 0.0;
        
        return $this->save();
    }
}
