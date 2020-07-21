<?php
/**
 * License
 * @author mnemonic88uk
 * @copyright 2020 mnemonic88uk
 * @license https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

class NovaPayProductCollection
{
    /**
     * @var NovaPayProduct[]
     */
    private $products = array();

    /**
     * @return NovaPayProduct[]
     */
    public function getProducts()
    {
        return $this->products;
    }

    /**
     * @param NovaPayProduct $product
     */
    public function addProduct(NovaPayProduct $product)
    {
        $this->products[] = $product;
    }

    /**
     * @return string|null
     */
    public function getJson()
    {
        $products = array();

        foreach ($this->products as $product) {
            $products[] = $product->getNovaPayData();
        }

        if (count($products)) {
            return json_encode($products);
        }
        
        return null;
    }
}
