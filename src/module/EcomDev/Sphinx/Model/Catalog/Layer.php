<?php

class EcomDev_Sphinx_Model_Catalog_Layer
    extends Mage_Catalog_Model_Layer
    implements EcomDev_Sphinx_Model_LayerInterface
{
    /**
     * Applies request object of controller into layer
     *
     * @return $this
     */
    public function applyRequest(Mage_Core_Controller_Request_Http $request)
    {
        Mage::getSingleton('ecomdev_sphinx/config')->getScope()
            ->setLayer($this)
            ->applyRequest($request);
        return $this;
    }

    /**
     * Fetches layered data
     *
     * @return $this
     */
    public function fetchData()
    {
        Mage::getSingleton('ecomdev_sphinx/config')
            ->getScope()->fetchData();

        return $this;
    }

    /**
     * Get current layer product collection
     *
     * @return EcomDev_Sphinx_Model_Resource_Product_Collection
     */
    public function getProductCollection()
    {
        if (isset($this->_productCollections[$this->getCurrentCategory()->getId()])) {
            $collection = $this->_productCollections[$this->getCurrentCategory()->getId()];
        } else {
            $collection = Mage::getResourceModel('ecomdev_sphinx/product_collection')
                ->setStoreId($this->getCurrentCategory()->getStoreId())
                ->addCategoryFilter($this->getCurrentCategory());
            
            $collection->setLayer($this);
            $this->prepareProductCollection($collection);
            $this->_productCollections[$this->getCurrentCategory()->getId()] = $collection;
        }
        
        return $collection;
    }
}
