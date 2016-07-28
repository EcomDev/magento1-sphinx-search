<?php

use EcomDev_Sphinx_Model_Sphinx_Facet_Filter_OptionInterface as OptionInterface;
use EcomDev_Sphinx_Model_Sphinx_FacetInterface as FacetInterface;

/**
 * Renderer for category link
 *
 * @method EcomDev_Sphinx_Model_Sphinx_Facet_Category getFacet()
 */
class EcomDev_Sphinx_Block_Layer_Facet_Renderer_Link
    extends EcomDev_Sphinx_Block_Layer_Facet_AbstractRenderer
{
    public function getTotalCount()
    {
        $currentCategory = $this->getFacet()->getCurrentCategoryData();
        
        return isset($currentCategory['root_product_count']) ? $currentCategory['root_product_count'] : 0;
    }
    
    public function getCategoryTree()
    {
        if ($this->hasData('category_tree')) {
            return $this->_getData('category_tree');
        }

        $categoryData = $this->getFacet()->getCurrentCategoryData();
        $categoryPathFilter = false;

        if (isset($categoryData['category_filter'])) {
            switch ($categoryData['category_filter']['include_same_level']) {
                case EcomDev_Sphinx_Model_Source_Level::LEVEL_CUSTOM:
                    $minLevel = (int)$categoryData['category_filter']['top_category_level'];
                    if ($minLevel === 0) {
                        $minLevel = (int)$categoryData['level'];
                    }
                    $parents = explode('/', $categoryData['path']);
                    if (count($parents) > $minLevel) {
                        $parents = array_slice($parents, 0, $minLevel+1);
                    }
                    $categoryPathFilter = implode('/', $parents);
                    break;

                case EcomDev_Sphinx_Model_Source_Level::LEVEL_SAME:
                    $categoryPathFilter = dirname($categoryData['path']);
                    break;
                default:
                    $categoryPathFilter = $categoryData['path'];
                    break;

            }
        } elseif (isset($categoryData['path'])) {
            $categoryPathFilter = $categoryData['path'];
        }

        $map = array();
        /* @var $categories Mage_Catalog_Model_Category[] */
        $categories = array();

        foreach ($this->getFacet()->getCategoryData() as $category) {
            $proxy = (object)['visible' => (bool)$category['include_in_menu']];
            Mage::dispatchEvent(
                'ecomdev_sphinx_facet_renderer_category_tree_is_visible',
                ['proxy' => $proxy, 'category_data' => $category]
            );

            if (!$proxy->visible) {
                continue;
            }

            $map[$category['path']] = Mage::getModel('catalog/category')
                ->setData($category)
                ->setId($category['category_id']);

            if (isset($map[dirname($category['path'])])) {
                $parentCategory = $map[dirname($category['path'])];
                $allNodes = $parentCategory->getData('child_nodes');
                if (!is_array($allNodes)) {
                    $allNodes = [];
                }

                $allNodes[] = $map[$category['path']];
                $parentCategory->setData('child_nodes', $allNodes);
            } elseif (!$categoryPathFilter ||
                $categoryPathFilter === dirname($category['path']) ||
                dirname($categoryPathFilter) === dirname($category['path'])) {
                $categories[] = $map[$category['path']];
            }
        }

        $this->setData('category_tree', $categories);
        return $categories;
    }

    /**
     * Returns true if it is current category
     *
     * @param $category
     * @return bool
     */
    public function isCurrentCategory($category)
    {
        $categoryData = $this->getFacet()->getCurrentCategoryData();
        return isset($categoryData['entity_id']) && $categoryData['entity_id'] == $category->getId();
    }

    /**
     * Returns a true if there filter should be visible or not
     *
     * Is selected call is used to prevent situation,
     * if some value is already selected
     *
     * @param FacetInterface $facet
     * @return bool
     */
    public function isVisible(FacetInterface $facet)
    {
        return count($facet->getCategoryData()) > 1;
    }

}
