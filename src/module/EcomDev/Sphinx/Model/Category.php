<?php

class EcomDev_Sphinx_Model_Category
    extends Mage_Catalog_Model_Category
{
    public function getParentCategories()
    {
        if (!$this->hasData('parent_categories')) {
            $pathInStore = explode(',', $this->getPathInStore());

            array_shift($pathInStore);
            $pathIds = array_map('intval', array_reverse($pathInStore));

            $result = $this->fetchCategoriesByIdentifiers($pathIds);
            $this->setData('parent_categories', $result);
        }
        
        return $this->_getData('parent_categories');
    }

    /**
     * Returns a sphinx scope id assigned to the current category
     *
     * @return int
     */
    public function getSphinxScope()
    {
        if ($this->_getData('sphinx_scope')) {
            return $this->_getData('sphinx_scope');
        }

        foreach ($this->getParentCategories() as $category) {
            if ($category->getSphinxScope()) {
                return $category->getSphinxScope();
            }
        }

        return null;
    }

    /**
     * @param $pathIds
     *
     * @return array
     */
    private function fetchCategoriesByIdentifiers($pathIds)
    {
        if (empty($pathIds)) {
            return [];
        }

        $container = Mage::getSingleton('ecomdev_sphinx/config')
            ->getContainer();

        $query = $container->queryBuilder();
        $items = $query
            ->select(
                'name', 'request_path',
                $query->expr('category_id as entity_id'),
                'sphinx_scope'
            )
            ->from($container->getIndexNames('category'))
            ->where('id', 'IN', $pathIds)
            ->where('is_active', '=', 1)
            ->orderBy('level', 'desc')
            ->execute();

        $result = [];
        foreach ($items as $data) {
            $result[$data['entity_id']] = Mage::getModel('catalog/category')->setData($data);
        }
        return $result;
    }
}
