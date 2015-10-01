<?php

class EcomDev_Sphinx_Model_Catalog_Layer
    extends Mage_Catalog_Model_Layer
    implements EcomDev_Sphinx_Model_LayerInterface
{
    private $scope;

    /**
     * Returns current scope instance
     *
     * @return EcomDev_Sphinx_Model_Scope
     */
    public function getScope()
    {
        if ($this->scope === null) {
            $scope = null;
            if ($this->getCurrentCategory()) {
                $scope = $this->getCurrentCategory()->getSphinxScope();
            }

            $this->scope = Mage::getSingleton('ecomdev_sphinx/config')->getScope($scope);
            $this->scope->setLayer($this);
        }

        return $this->scope;
    }

    /**
     * Applies request object of controller into layer
     *
     * @return $this
     */
    public function applyRequest(Mage_Core_Controller_Request_Http $request)
    {
        $this->getScope()
            ->setCurrentOrder('position')
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
        $this->getScope()->fetchData();
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
            /** @var EcomDev_Sphinx_Model_Resource_Product_Collection $collection */
            $collection = Mage::getResourceModel('ecomdev_sphinx/product_collection')
                ->setStoreId($this->getCurrentCategory()->getStoreId());

            $collection->addCategoryFilter($this->getCurrentCategory());
            $collection->setLayer($this);

            $collection->setFlag(
                EcomDev_Sphinx_Model_Resource_Product_Collection::FLAG_ONLY_DIRECT_CATEGORY,
                (bool)$this->getScope()->getConfigurationValue('category_filter/only_direct_products')
            );

            $this->prepareProductCollection($collection);
            $this->_productCollections[$this->getCurrentCategory()->getId()] = $collection;
        }
        
        return $collection;
    }
}
