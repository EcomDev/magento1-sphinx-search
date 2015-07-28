<?php

class EcomDev_Sphinx_Model_Category
    extends Mage_Catalog_Model_Category
{
    public function getParentCategories()
    {
        if (!$this->hasData('parent_categories')) {
            $container = Mage::getSingleton('ecomdev_sphinx/config')
                ->getContainer();
            $query = $container->queryBuilder();
            
            $pathIds = array_map('intval', array_reverse(explode(',', $this->getPathInStore())));
            $items = $query
                ->select(
                    'name', 'request_path', $query->expr('category_id as entity_id')
                )
                ->from($container->getIndexNames('category'))
                ->where('category_id', 'IN', $pathIds)
                ->where('is_active', 1)
                ->execute();
            
            $result = array();
            foreach ($items as $data) {
                $result[$data['entity_id']] = Mage::getModel('catalog/category')->setData($data);
            }
            $this->setData('parent_categories', $result);
        }
        
        return $this->_getData('parent_categories');
    }
}
