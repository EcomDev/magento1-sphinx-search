<?php

class EcomDev_Sphinx_Model_Search_Layer
    extends Mage_CatalogSearch_Model_Layer
    implements EcomDev_Sphinx_Model_LayerInterface
{
    /**
     * Sphinx scope
     *
     * @var EcomDev_Sphinx_Model_Scope
     */
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
        Mage::getSingleton('ecomdev_sphinx/config')->getContainer()->activateSearchMode();
        $this->getScope()
            ->setCurrentOrder('relevance')
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
        Mage::getSingleton('ecomdev_sphinx/config')->getScope()
            ->fetchData();
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
            $collection = Mage::getResourceModel('ecomdev_sphinx/product_collection');
            $collection->setLayer($this);
            $this->prepareProductCollection($collection);
            $this->_productCollections[$this->getCurrentCategory()->getId()] = $collection;
        }
        return $collection;
    }
}
