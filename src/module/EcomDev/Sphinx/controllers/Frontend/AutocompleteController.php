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


        $keywordModel = Mage::getModel('ecomdev_sphinx/index_keyword');

        $keywords = $keywordModel->extractKeywords($this->getRequest()->getParam('q'));

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


        $layer = Mage::getSingleton('ecomdev_sphinx/search_layer');
        $scope = $layer->getScope();
        $scope->setPageSize(10);
        $scope->activateSearchMode();

        $suggestions = $keywordModel->suggestions($keywords, $scope, 10);

        $collection = $layer->getProductCollection();

        if ($suggestions) {
            $collection->addSearchFilter(current($suggestions));
        } else {
            $collection->addSearchFilter(implode(' ', $keywords));
        }

        $scope->fetchCollection($collection);

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
        $result->setScope($scope);
        $result->setKeywordModel($keywordModel);
        $result->setKeywords($keywords);

        Mage::register('search_result', $result);
        $this->loadLayout(false);
        $this->renderLayout();
    }
}
