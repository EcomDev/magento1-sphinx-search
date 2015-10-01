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
     * Layer model interface
     *
     * @return EcomDev_Sphinx_Model_LayerInterface
     */
    public function getLayer()
    {
        return Mage::registry('sphinx_layer');
    }

    /**
     * Loads spinx related data
     *
     * @return $this
     */
    public function loadData()
    {
        if ($this->_isLoaded || !$this->getLayer()) {
            return $this;
        }

        if ($this->getListBlock()) {
            $this->getLayer()->getScope()->setPageSize(
                $this->getListBlock()->getToolbarBlock()->getLimit()
            );

            $this->getListBlock()->setAvailableOrders(
                $this->getLayer()->getScope()->getSortOrders()
            );

            $this->getListBlock()->setSortBy(
                $this->getLayer()->getScope()->getCurrentOrder()
            );
        }

        $this->getLayer()->getScope()->fetchData();
        $this->_isLoaded = true;
        return $this;
    }

    protected function _toHtml()
    {
        $this->loadData();
        return '';
    }
}
