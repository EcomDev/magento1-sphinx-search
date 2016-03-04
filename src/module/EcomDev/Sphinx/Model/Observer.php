<?php

class EcomDev_Sphinx_Model_Observer
{
    /**
     * Replaces handles
     * 
     * @var array
     */
    protected $_addHandles = false;

    /**
     * Current layer model
     * 
     * @var EcomDev_Sphinx_Model_LayerInterface
     */
    protected $_layerModel;

    /**
     * Should return JSON string
     * 
     * @var bool
     */
    protected $_returnJson = false;

    /**
     *
     *
     * @var string[]
     */
    protected $_availableHandles = array(
        'catalog_category_default',
        'catalog_category_layered',
        'catalogsearch_result_index'
    );
    
    /**
     * Enables sphinx search on category initialization
     * 
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function onInitCategory(Varien_Event_Observer $observer)
    {
        if ($this->_getConfig()->isEnabled()) {
            $this->_layerModel = Mage::getModel('ecomdev_sphinx/catalog_layer');
            if (Mage::registry('_singleton/catalog/layer')) {
                Mage::unregister('_singleton/catalog/layer');
            }
            Mage::register(
                '_singleton/catalog/layer', 
                $this->_layerModel
            );
            
            $this->_replaceCurrentCategory($observer->getCategory());
            
            $this->_addHandles = true;
            $this->_layerModel->applyRequest(
                $observer->getControllerAction()
                    ->getRequest()
            );

            Mage::dispatchEvent('ecomdev_sphinx_init_category_view', [
                'layer' => $this->_layerModel,
                'category' => Mage::registry('current_category')
            ]);
        }
        
        return $this;
    }

    /**
     * Enables sphinx breadcrumbs
     *
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function onInitProduct(Varien_Event_Observer $observer)
    {
        if ($this->_getConfig()->isEnabled()) {
            if ($observer->getProduct()->getData('category')) {
                $this->_replaceCurrentCategory($observer->getProduct()->getCategory());
                $observer->getProduct()->setCategory(Mage::registry('current_category'));
            }
        }

        return $this;
    }
    
    protected function _replaceCurrentCategory($category)
    {
        Mage::unregister('current_category');
        Mage::register(
            'current_category',
            Mage::getModel('ecomdev_sphinx/category')->setData(
                $category->getData()
            )
        );
    }

    /**
     * Adds catalog categories to top menu
     *
     * @param Varien_Event_Observer $observer
     */
    public function addCatalogToTopmenuItems(Varien_Event_Observer $observer)
    {
        if ($this->_getConfig()->isEnabled() && $this->_getConfig()->getConfig('replace_menu', 'general')) {
            $block = $observer->getEvent()->getBlock();
            $block->addCacheTag(Mage_Catalog_Model_Category::CACHE_TAG);
            $this->_addCategoriesToMenu(
                $this->_getStoreCategoriesTree(), $observer->getMenu(), $block
            );
        } else {
            Mage::getSingleton('catalog/observer')->addCatalogToTopmenuItems($observer);
        }
    }

    /**
     * Returns menu items from sphinx
     *
     * @return array[]|bool
     */
    public function getCatalogMenuItems()
    {
        if ($this->_getConfig()->isEnabled() && $this->_getConfig()->getConfig('replace_menu', 'general')) {
            return $this->_getStoreCategoriesTree();
        }

        return false;
    }
    
    protected function _getStoreCategoriesTree()
    {
        $query = $this->_getConfig()->getContainer()->queryBuilder();
        $indexNames = $this->_getConfig()->getContainer()->getIndexNames('category');
        $result = $query->select('path', 'level')
            ->from($indexNames)
            ->where('id', (int)Mage::app()->getStore()->getRootCategoryId())
            ->limit(1)
            ->execute()
            ->store();

        if ($result->getCount() === 0) {
            return [];
        }

        $category = $result[0];

        $recursionLevel  = max(0, (int) Mage::app()->getStore()->getConfig('catalog/navigation/max_depth'));
        $query = $this->_getConfig()->getContainer()->queryBuilder();
        $query
            ->select(
                $query->expr('category_id as entity_id'),
                'path',
                'name',
                'is_active',
                'request_path',
                'position',
                'include_in_menu',
                'level'
            )
            ->from($indexNames)
            ->where('is_active','=', 1)
            ->orderBy('level', 'asc')
            ->orderBy('position', 'asc')
            ->match('path', $query->expr(
                '"^' . $query->escapeMatch($category['path']) . '"'
            ))
            ->limit(2000);
            
        if ($recursionLevel > 0) {
            $query->where('level', '<', $category['level'] + $recursionLevel);
        }
        
        $result = array();
        $parents = array();

        foreach ($query->execute() as $item) {
            if (dirname($item['path']) === $category['path']) {
                $item['children'] = array();
                $index = count($result);
                $result[$index] = $item;
                $parents[$item['path']] = &$result[$index];
            } elseif (isset($parents[dirname($item['path'])])) {
                $item['children'] = array();
                $index = count($parents[dirname($item['path'])]['children']); 
                $parents[dirname($item['path'])]['children'][$index] = $item; 
                $parents[$item['path']] = &$parents[dirname($item['path'])]['children'][$index];
            }
        }
        
        return $result;
    }

    /**
     * Recursively adds categories to top menu
     *
     * @param array $tree
     * @param Varien_Data_Tree_Node $parentCategoryNode
     * @param Mage_Page_Block_Html_Topmenu $menuBlock
     * @param Mage_Catalog_Model_Category|null $categoryModel
     */
    protected function _addCategoriesToMenu($tree, $parentCategoryNode, $menuBlock, $categoryModel = null)
    {
        if ($categoryModel === null) {
            $categoryModel = Mage::getModel('catalog/category');
        }
        
        foreach ($tree as $data) {
            $categoryModel->unsetData()
                ->setData($data);


            if (!$categoryModel->getIncludeInMenu()) {
                continue;
            }

            $nodeId = 'category-node-' . $categoryModel->getId();
            $categoryData = array(
                'name' => $categoryModel->getName(),
                'id' => $nodeId,
                'url' => $categoryModel->getUrl(),
                'is_active' => $this->_isActiveMenuCategory($categoryModel),
                'include_in_menu' => $categoryModel->getIncludeInMenu()
            );

            $categoryNode = new Varien_Data_Tree_Node($categoryData, 'id', $parentCategoryNode->getTree(), $parentCategoryNode);
            $parentCategoryNode->addChild($categoryNode);

            if ($data['children']) {
                $this->_addCategoriesToMenu($data['children'], $categoryNode, $menuBlock);
            }
        }
    }

    /**
     * Checks whether category belongs to active category's path
     *
     * @param Mage_Catalog_Model_Category $category
     * @return bool
     */
    protected function _isActiveMenuCategory($category)
    {
        $currentCategory = Mage::registry('current_category');
        
        if (!$currentCategory) {
            return false;
        }
        
        if (!$currentCategory->hasData('_exploded_path_in_store')) {
            $currentCategory->setData(
                '_exploded_path_in_store', 
                explode(',', $currentCategory->getPathInStore())
            );
        }
        
        
        return in_array($category->getId(), $currentCategory->getData('_exploded_path_in_store'));
    }
    
    /**
     * Enables sphinx search on search initialization
     *
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function onInitSearch(Varien_Event_Observer $observer)
    {
        if ($this->_getConfig()->isSearchEnabled()) {
            $this->_layerModel = Mage::getModel('ecomdev_sphinx/search_layer');

            if (Mage::registry('_singleton/catalogsearch/layer')) {
                Mage::unregister('_singleton/catalogsearch/layer');
            }

            if (Mage::registry('_singleton/catalog/layer')) {
                Mage::unregister('_singleton/catalog/layer');
            }

            Mage::register(
                '_singleton/catalogsearch/layer', $this->_layerModel
            );
            
            Mage::register(
                '_singleton/catalog/layer', $this->_layerModel
            );

            $this->_addHandles = true;
            $this->_layerModel->applyRequest(
                $observer->getControllerAction()
                    ->getRequest()
            );
        }

        return $this;
    }
    
    public function onLayoutHandlesLoad(Varien_Event_Observer $observer)
    {
        if ($this->_addHandles) {
            $this->_addHandles = false;
            $controller = $observer->getAction();
            if ($controller && $controller->getRequest()->isXmlHttpRequest()) {
                $this->_returnJson = true;
                $controller->setFlag('',  'no-renderLayout', true);
            }
            /** @var Mage_Core_Model_Layout $layout */
            $layout = $observer->getLayout();
            $applyHandles = array_intersect($this->_availableHandles, $layout->getUpdate()->getHandles());
            
            foreach ($applyHandles as $handle) {
                $layout->getUpdate()->addHandle($handle . '_ecomdev_sphinx');
                
                if ($this->_returnJson) {
                    $layout->getUpdate()->addHandle($handle . '_ecomdev_sphinx_json');
                }
            }
        }
    }

    /**
     * Fetches data from sphinx
     * 
     * @param Varien_Event_Observer $observer
     */
    public function onLayoutBeforeRender(Varien_Event_Observer $observer)
    {
        if ($this->_layerModel !== null) {
            $this->registerLayer();
        }
    }

    /**
     * Registers layer
     *
     */
    private function registerLayer()
    {
        if (Mage::registry('sphinx_layer')) {
            Mage::unregister('sphinx_layer');
        }

        Mage::register('sphinx_layer', $this->_layerModel);
    }

    /**
     * Applies JSON representation of page
     * 
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function applyJsonView(Varien_Event_Observer $observer)
    {
        if (!$this->_returnJson || !$this->_layerModel) {
            return $this;
        }

        $this->registerLayer();
        $this->_returnJson = false;
        $controller = $observer->getControllerAction();
        $navigationBlock = $controller->getLayout()->getBlock('sphinx.leftnav');
        $loaderBlock = $controller->getLayout()->getBlock('sphinx.loader');
        $result = array();
        
        if ($navigationBlock && $loaderBlock->getListBlock() && $loaderBlock) {
            $loaderBlock->toHtml();
            $result['filters'] = $navigationBlock->toHtml();
            $result['products'] = $loaderBlock->getListBlock()->toHtml();
        }
        
        if ($controller->getLayout()->getBlock('profiler')) {
            $result['profiler'] = $controller->getLayout()->getBlock('profiler')->toHtml(); 
        }

        $controller->getResponse()
            ->setBody(json_encode($result));
        
        return $this;
    }

    /**
     * Returns sphinx configuration
     * 
     * @return EcomDev_Sphinx_Model_Config
     */
    protected function _getConfig()
    {
        return Mage::getSingleton('ecomdev_sphinx/config');
    }
}
