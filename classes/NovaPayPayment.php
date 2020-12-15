<?php
/**
 * License
 * @author mnemonic88uk
 * @copyright 2020 mnemonic88uk
 * @license https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

class NovaPayPayment extends ObjectModel implements NovaPay\DataSourceInterface
{
    /**
     * @var string
     */
    public $merchant_id = null;

    /**
     * @var string
     */
    public $session_id = null;

    /**
     * @var string
     */
    public $payment_id = null;

    /**
     * @var string
     */
    public $external_id = null;

    /**
     * @var float
     */
    public $amount = null;

    /**
     * @var string JSON
     */
    public $products = null;

    /**
     * @var int
     */
    public $use_hold = 0;

    /**
     * @var string JSON
     */
    public $delivery = null;

    /**
     * @var float
     */
    public $delivery_price = null;
    
    /**
     * @var array
     */
    public static $definition = array(
        'table' => 'novapay_payment',
        'primary' => 'id_novapay_payment',
        'fields' => array(
            'merchant_id' => array('type' => self::TYPE_STRING, 'required' => true, 'size' => 50),
            'session_id' => array('type' => self::TYPE_STRING, 'required' => true, 'size' => 50),
            'payment_id' => array('type' => self::TYPE_STRING, 'size' => 50),
            'external_id' => array('type' => self::TYPE_STRING, 'required' => true, 'size' => 9),
            'amount' => array('type' => self::TYPE_FLOAT, 'required' => true),
            'products' => array('type' => self::TYPE_STRING, 'required' => true),
            'use_hold' => array('type' => self::TYPE_BOOL, 'required' => true),
            'delivery' => array('type' => self::TYPE_STRING),
            'delivery_price' => array('type' => self::TYPE_FLOAT),
        ),
    );

    /**
     * @param string $orderReference
     * @return NovaPayPayment|null
     */
    public static function getPaymentByOrderReference($orderReference)
    {
        $query = (new DbQuery())
            ->select('p.id_novapay_payment')
            ->from('novapay_payment', 'p')
            ->leftJoin('orders', 'o', 'o.id_order = p.external_id')
            ->where('o.reference = \''.(string)$orderReference.'\'');

        if (!$id = Db::getInstance()->getValue($query->build())) {
            return null;
        }
        
        $payment = new NovaPayPayment($id);
        if (!Validate::isLoadedObject($payment)) {
            return null;
        }

        return $payment;
    }
    
    /**
     * @return array
     */
    public function getNovaPayData()
    {
        $data = array(
            'merchant_id' => $this->merchant_id,
            'session_id' => $this->session_id,
            'amount' => $this->amount,
        );

        if ($this->external_id) {
            $data['external_id'] = $this->external_id;
        }

        if ($this->products) {
            $data['products'] = json_decode($this->products, true);
        }

        if ($this->use_hold) {
            $data['use_hold'] = $this->use_hold;
        }

        if ($this->delivery) {
            $data['delivery'] = json_decode($this->delivery, true);
        }

        return $data;
    }
}
