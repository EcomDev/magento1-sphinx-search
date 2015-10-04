<?php

class EcomDev_Sphinx_Frontend_AutocompleteController extends Mage_Core_Controller_Front_Action
{
    /**
     * Returns sphinx configuration
     *
     * @return EcomDev_Sphinx_Model_Config
     */
    protected function _getConfig()
    {
        return Mage::getSingleton('ecomdev_sphinx/config');
    }

    /**
     * Returns index action
     *
     */
    public function indexAction()
    {
        if (!$this->_getConfig()->isEnabled()) {
            $this->norouteAction();
            return;
        }

        $keywords = trim($this->getRequest()->getParam('q'));
        $keywords = array_filter(array_map('trim', explode(' ', $keywords)));

        $format = $this->getRequest()->getParam('format');

        if (empty($keywords)) {
            if ($format === 'json') {
                $this->getResponse()->setBody(json_encode(
                    ['suggestions' => [], 'results' => []]
                ));
            } else {
                $this->getResponse()->setBody('');
            }

            return;
        }

        $keywordModel = Mage::getModel('ecomdev_sphinx/index_keyword');

        $layer = Mage::getSingleton('ecomdev_sphinx/search_layer');
        $scope = $layer->getScope();
        $scope->setPageSize(5);
        $scope->activateSearchMode();

        $suggestions = $keywordModel->suggestions($keywords, $scope);

        $collection = $layer->getProductCollection();

        if ($suggestions) {
            $firstOption = current($suggestions);
            $collection->addSearchFilter($firstOption);
            $scope->fetchCollection($collection);
        }

        if ($format === 'json') {
            $products = [];
            foreach ($collection as $item) {
                $products[$item->getId()] = [
                    'name' => $item->getName(),
                    'price' => $item->getMinimalPrice(),
                    'stock_status' => $item->getStockStatus(),
                    'image' => $item->getSmallImage()
                ];
            }

            $this->getResponse()->setBody(json_encode(
                ['suggestions' => $suggestions, 'products' => $products]
            ));
            return;
        }

        $result = new Varien_Object();
        $result->setSuggestions($suggestions);
        $result->setProducts($collection);

        Mage::register('search_result', $result);
        $this->loadLayout(false);
        $this->renderLayout();
    }
}
