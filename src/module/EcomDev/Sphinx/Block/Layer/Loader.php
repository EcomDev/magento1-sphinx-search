<?php

class EcomDev_Sphinx_Block_Layer_Loader
    extends Mage_Core_Block_Abstract
{
    protected $_isLoaded = false;

    /**
     * Returns list block that outputs our products
     *
     * @return Mage_Catalog_Block_Product_List|bool
     */
    public function getListBlock()
    {
        return $this->getLayout()->getBlock($this->_getData('list_block'));
    }

    /**
     * Configuration model
     *
     * @return EcomDev_Sphinx_Model_Config
     */
    public function getConfig()
    {
        return Mage::getSingleton('ecomdev_sphinx/config');
    }

    /**
     * Loads spinx related data
     *
     * @return $this
     */
    public function loadData()
    {
        if ($this->_isLoaded) {
            return $this;
        }

        if ($this->getListBlock()) {
            $this->getConfig()->getScope()->setPageSize(
                $this->getListBlock()->getToolbarBlock()->getLimit()
            );


            $this->getListBlock()->setAvailableOrders(
                $this->getConfig()->getScope()->getSortOrders()
            );

            $this->getListBlock()->setSortBy(
                $this->getConfig()->getScope()->getCurrentOrder()
            );
        }

        if (Mage::registry('sphinx_layer')) {
            Mage::registry('sphinx_layer')->fetchData();
        }

        $this->_isLoaded = true;
        return $this;
    }

    protected function _toHtml()
    {
        $this->loadData();
        return '';
    }
}
