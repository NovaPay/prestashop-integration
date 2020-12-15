<?php
/**
 * License
 * @author mnemonic88uk
 * @copyright 2020 mnemonic88uk
 * @license https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

namespace NovaPay;

class ProductCollection
{
    /**
     * @var Product[]
     */
    private $products = array();

    /**
     * @return Product[]
     */
    public function getProducts()
    {
        return $this->products;
    }

    /**
     * @param Product $product
     */
    public function addProduct(Product $product)
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
