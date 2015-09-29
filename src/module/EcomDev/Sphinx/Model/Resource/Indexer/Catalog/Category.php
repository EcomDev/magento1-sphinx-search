<?php

use EcomDev_Sphinx_Model_Indexer_Catalog_Category as CategoryIndexer;

class EcomDev_Sphinx_Model_Resource_Indexer_Catalog_Category
    extends EcomDev_Sphinx_Model_Resource_Indexer_Catalog_AbstractIndexer
{

    /**
     * Resource initialization
     *
     */
    protected function _construct()
    {
        $this->_init('ecomdev_sphinx/index_category', 'document_id');
    }

    /**
     * Process category save
     *
     * @param Mage_Index_Model_Event $event
     * @return $this
     */
    public function sphinxUpdateMassAction(Mage_Index_Model_Event $event)
    {
        $data = $event->getNewData();
        if (isset($data[CategoryIndexer::EVENT_CATEGORY_IDS])) {
            $categoryIds = $data[CategoryIndexer::EVENT_CATEGORY_IDS];
        } else {
            return $this;
        }

        $limit = $this->_getAllRelatedCategoryIds($categoryIds);
        $this->_reindexCategories($limit);
    }

    /**
     * Reindex all categories index
     * 
     * @return $this
     */
    public function reindexAll()
    {
        $this->_validateTriggers('category', ['ecomdev_sphinx/index_category', 'category_id']);
        $this->_reindexCategories();
        return $this;
    }

    /**
     * Returns all possible categories
     * 
     * @param array $categoryIds
     * @return Varien_Db_Select
     */
    protected function _getAllRelatedCategoryIds(array $categoryIds)
    {
        $select = $this->_getIndexAdapter()->select()
            ->from($this->getTable('catalog/category'), 'path')
            ->where('entity_id IN(?)', $categoryIds);

        $pathIdList = $this->_getIndexAdapter()->fetchCol($select);

        $select->reset()
            ->from($this->getTable('catalog/category'), 'entity_id')
            ->where('path IN(?)', $pathIdList);

        foreach ($pathIdList as $path) {
            $select->orWhere('path LIKE ?', $path . '%');
        }

        return $select;
    }

    /**
     * Re-indexes categories with applying the limits
     * 
     * @param null|Zend_Db_Select|array $limit
     * @return $this
     */
    protected function _reindexCategories($limit = null)
    {
        $this->_transactional(function ($limit) {
            $this->_generateMainData($limit);
            $this->_removeInvalidRecords($limit);
        }, $limit !== null, $limit);

        return $this;
    }

    /**
     * Generates main index data
     * 
     * @param null|Zend_Db_Select|array $limit
     * @return $this
     */
    protected function _generateMainData($limit = null)
    {
        $columns = [
            'category_id' => 'category.entity_id',
            'store_id' => 'store.store_id',
            'path' => 'category.path',
            'position' => 'category.position',
            'level' => 'category.level'
        ];

        $select = $this->_getIndexAdapter()->select();
        $select
            ->from(['store' => $this->getTable('core/store')], ['store_id'])
            ->join(['group' => $this->getTable('core/store_group')], 'group.group_id = store.group_id', [])
            ->join(['category' => $this->getTable('catalog/category')], 'category.entity_id = group.root_category_id', ['path'])
            ->where('store.store_id <> ?', 0);

        $stmt = $this->_getIndexAdapter()->query($select);

        $storeByPath = [];
        while ($row = $stmt->fetch()) {
            $storeByPath[$row['path']][] = $row['store_id'];
        }

        $condition = '%1$s.entity_id = category.entity_id and %1$s.attribute_id = %3$s and %1$s.store_id = %2$s';
        $keys = array_keys($columns);
        $keys[] = 'is_active';

        foreach ($storeByPath as $path => $storeIds) {
            $select->reset();

            $storeCondition = $this->_getIndexAdapter()->quoteInto(
                'store.store_id IN(?)', $storeIds
            );

            $select
                ->from(['category' => $this->getTable('catalog/category')], [])
                ->join(['store' => $this->getTable('core/store')], $storeCondition, [])
                ->columns($columns)
                ->where('category.path LIKE ?', $path . '%');

            $this->_joinAttribute(
                $select,
                $condition ,
                'is_active',
                Mage_Catalog_Model_Category::ENTITY,
                'store'
            );

            if ($limit !== null) {
                $select->where('category.entity_id IN(?)', $limit);
            }

            $this->insertFromSelect($select, $this->getMainTable(), $keys);
        }

        return $this;
    }

    /**
     * Removes invalid data from index
     *
     * @param null|Zend_Db_Select|array $limit
     * @return $this
     */
    protected function _removeInvalidRecords($limit = null)
    {
        $select = $this->_getIndexAdapter()->select();

        $select
            ->from(['store' => $this->getTable('core/store')], ['store_id'])
            ->join(['group' => $this->getTable('core/store_group')], 'group.group_id = store.group_id', [])
            ->join(['category' => $this->getTable('catalog/category')], 'category.entity_id = group.root_category_id', ['path']);

        $stmt = $this->_getIndexAdapter()->query($select);

        $storeByPath = [];
        while ($row = $stmt->fetch()) {
            $storeByPath[$row['path']][] = $row['store_id'];
        }

        $conditions = [];
        if ($limit !== null) {
            $conditions['category_id IN(?)'] = $limit;
        }

        foreach ($storeByPath as $path => $storeIds) {
            // Remove invalid records per path
            $this->_getIndexAdapter()->delete(
                $this->getMainTable(),
                $conditions + [
                    'store_id IN(?)' => $storeIds,
                    'path NOT LIKE ?' => $path . '%'
                ]
            );
        }

        return $this;
    }
}
