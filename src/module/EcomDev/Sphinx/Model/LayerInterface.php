<?php

interface EcomDev_Sphinx_Model_LayerInterface
{
    /**
     * Returns current category instance
     * 
     * @return Mage_Catalog_Model_Category
     */
    public function getCurrentCategory();

    /**
     * Returns current store id
     * 
     * @return Mage_Core_Model_Store
     */
    public function getCurrentStore();

    /**
     * Applies request object of controller into layer
     * 
     * @return $this
     */
    public function applyRequest(Mage_Core_Controller_Request_Http $request);

    /**
     * Fetches layered data
     * 
     * @return $this
     */
    public function fetchData();

    /**
     * Returns an instance of product collection
     * 
     * @return EcomDev_Sphinx_Model_Resource_Product_Collection
     */
    public function getProductCollection();

    /**
     * Returns scope instance
     *
     * @return EcomDev_Sphinx_Model_Scope
     */
    public function getScope();
}
