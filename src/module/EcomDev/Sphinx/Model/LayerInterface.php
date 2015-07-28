<?php

interface EcomDev_Sphinx_Model_LayerInterface
{
    /**
     * Returns list of available category names  
     * 
     * @return string[]
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
}
