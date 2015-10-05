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
    public function getCategoryTree()
    {
        if ($this->hasData('category_tree')) {
            return $this->_getData('category_tree');
        }

        $categoryData = $this->getFacet()->getCurrentCategoryData();
        $filterFirstLevel = false;

        if (isset($categoryData['path'])) {
            $filterFirstLevel = $categoryData['path'];
        }

        $map = array();
        /* @var $categories Mage_Catalog_Model_Category[] */
        $categories = array();

        foreach ($this->getFacet()->getCategoryData() as $category) {
            if (!$category['include_in_menu']) {
                continue;
            }

            $map[$category['path']] = Mage::getModel('catalog/category')
                ->setData($category)
                ->setId($category['category_id']);
            if (isset($map[dirname($category['path'])])) {
                $parentCategory = $map[dirname($category['path'])];
                $allNodes = $parentCategory->getData('child_nodes');
                if (!is_array($allNodes)) {
                    $allNodes = array();
                }

                $allNodes[] = $map[$category['path']];
                $parentCategory->setData('child_nodes', $allNodes);
            } elseif (!$filterFirstLevel ||
                $filterFirstLevel === dirname($category['path']) ||
                dirname($filterFirstLevel) === dirname($category['path'])) {
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