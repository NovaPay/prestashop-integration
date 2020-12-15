<?php
/**
 * License
 * @author mnemonic88uk
 * @copyright 2020 mnemonic88uk
 * @license https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

namespace NovaPay;

class Delivery implements DataSourceInterface
{
    /**
     * @var float
     */
    private $amount = 0.0;

    /**
     * @var float
     */
    private $volumeWeight = 0.0;

    /**
     * @var float
     */
    private $weight = 0.0;

    /**
     * @var string
     */
    private $recipientCityReference = '';

    /**
     * @var string
     */
    private $recipientWarehouseReference = '';

    /**
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param float $amount
     *
     * @return $this
     */
    public function setAmount($amount)
    {
        $this->amount = (float)$amount;

        return $this;
    }
    
    /**
     * @return float
     */
    public function getVolumeWeight()
    {
        return $this->volumeWeight;
    }

    /**
     * @param float $volumeWeight
     *
     * @return $this
     */
    public function setVolumeWeight($volumeWeight)
    {
        $this->volumeWeight = (float)$volumeWeight;

        return $this;
    }

    /**
     * @return float
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * @param float $weight
     *
     * @return $this
     */
    public function setWeight($weight)
    {
        $this->weight = (float)$weight;

        return $this;
    }

    /**
     * @return string
     */
    public function getRecipientCityReference()
    {
        return $this->recipientCityReference;
    }

    /**
     * @param string $cityReference
     *
     * @return $this
     */
    public function setRecipientCityReference($cityReference)
    {
        $this->recipientCityReference = (string)$cityReference;

        return $this;
    }

    /**
     * @return string
     */
    public function getRecipientWarehouseReference()
    {
        return $this->recipientWarehouseReference;
    }

    /**
     * @param string $warehouseReference
     *
     * @return $this
     */
    public function setRecipientWarehouseReference($warehouseReference)
    {
        $this->recipientWarehouseReference = (string)$warehouseReference;

        return $this;
    }

    /**
     * @param bool $addAmount
     *
     * @return array
     */
    public function getNovaPayData($addAmount = true)
    {
        $data = array(
            'volume_weight' => $this->volumeWeight,
            'weight' => $this->weight,
            'recipient_city' => $this->recipientCityReference,
            'recipient_warehouse' => $this->recipientWarehouseReference,
        );

        if ($addAmount) {
            $data['amount'] = $this->amount;
        }

        return $data;
    }

    /**
     * @param bool $addAmount
     *
     * @return string
     */
    public function getJson($addAmount = true)
    {
        return json_encode($this->getNovaPayData($addAmount));
    }
}
