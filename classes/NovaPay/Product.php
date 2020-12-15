<?php
/**
 * License
 * @author mnemonic88uk
 * @copyright 2020 mnemonic88uk
 * @license https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

namespace NovaPay;

class Product implements DataSourceInterface
{
    /**
     * @var string
     */
    private $description = '';

    /**
     * @var int
     */
    private $count = 0;

    /**
     * @var float
     */
    private $price = 0.0;

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     *
     * @return $this
     */
    public function setDescription($description)
    {
        if (is_string($description)) {
            $this->description = $description;
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * @param int $count
     *
     * @return $this
     */
    public function setCount($count)
    {
        $this->count = (int)$count;

        return $this;
    }

    /**
     * @return float
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param float $price
     *
     * @return $this
     */
    public function setPrice($price)
    {
        $this->price = (float)$price;

        return $this;
    }

    /**
     * @return array
     */
    public function getNovaPayData()
    {
        return array(
            'description' => $this->description,
            'count' => $this->count,
            'price' => $this->price,
        );
    }
}
