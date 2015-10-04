<?php

class EcomDev_Sphinx_Block_Autocomplete_Result
    extends Mage_Core_Block_Template
{
    /**
     * Returns suggested products
     *
     * @param int $limit
     * @return Mage_Catalog_Model_Product
     */
    public function getProducts($limit)
    {
        $products = [];

        if (!$this->getResult()->getProducts()) {
            return $products;
        }

        foreach ($this->getResult()->getProducts() as $product) {
            if (count($products) > $limit) {
                break;
            }

            $products[] = $product;
        }

        return $products;
    }

    /**
     * Returns suggested search phrases
     *
     * @param int $limit
     * @return string
     */
    public function getSuggestions($limit)
    {
        $suggestions = $this->getResult()->getSuggestions();
        if (!$suggestions) {
            return [];
        }

        if (count($suggestions) > $limit) {
            return array_slice($suggestions, 0, $limit);
        }

        return $suggestions;
    }

    /**
     * Returns result object
     *
     * @return Varien_Object
     */
    protected function getResult()
    {
        if (Mage::registry('search_result')) {
            return Mage::registry('search_result');
        }

        return new Varien_Object();
    }
}
