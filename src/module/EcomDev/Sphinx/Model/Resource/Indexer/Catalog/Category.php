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
    public function catalogCategorySave(Mage_Index_Model_Event $event)
    {
        $data = $event->getNewData();
        if (isset($data[CategoryIndexer::EVENT_CATEGORY_IDS])) {
            $categoryIds = $data[CategoryIndexer::EVENT_CATEGORY_IDS];
        } elseif (isset($data[CategoryIndexer::EVENT_CATEGORY_ID])) {
            $categoryIds = array($data[CategoryIndexer::EVENT_CATEGORY_ID]);
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
        $this->_createIndexTrigger(
            $this->getMainTable(),
            EcomDev_Sphinx_Model_Sphinx_Config_Index::INDEX_CATEGORY,
            'document_id'
        );
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
        $deleteWhere = '';
        if ($limit !== null) {
            $deleteWhere = array('category_id IN(?)' => $limit);
        }

        $this->_getIndexAdapter()->delete($this->getMainTable(), $deleteWhere);

        if ($limit === null) {
            $this->_getIndexAdapter()->changeTableAutoIncrement($this->getMainTable(), '1');
        }

        $this->_getIndexAdapter()->beginTransaction();
        $this->_generateMainData($limit);
        $this->_generateAttributeValues($limit);
        $this->_getIndexAdapter()->commit();
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
        $columns = array(
            'category_id' => 'category.entity_id',
            'store_id' => 'store.store_id',
            'path' => 'category.path',
            'position' => 'category.position',
            'level' => 'category.level'
        );

        $select = $this->_getIndexAdapter()->select();
        $select
            ->from(array('store' => $this->getTable('core/store')), array())
            ->join(array('group' => $this->getTable('core/store_group')), 'group.group_id = store.group_id', array())
            ->join(array('category' => $this->getTable('catalog/category')), 
                         "category.path LIKE CONCAT('1/', group.root_category_id, '/%') or category.path = CONCAT('1/', group.root_category_id)", array())
            ->columns($columns);

        if ($limit !== null) {
            $select->where('category.entity_id IN(?)', $limit);
        }

        $this->_getIndexAdapter()->query(
            $this->_getIndexAdapter()->insertFromSelect(
                $select, $this->getMainTable(), array_keys($columns), Varien_Db_Adapter_Interface::INSERT_ON_DUPLICATE
            )
        );

        return $this;
    }

    /**
     * Generates attribute values index
     *
     * @param null|Zend_Db_Select|array $limit
     * @return $this
     */
    protected function _generateAttributeValues($limit = null)
    {
        $attributes = array('name', 'thumbnail', 'image', 
                            'include_in_menu', 'description', 'is_active');

        $select = $this->_getIndexAdapter()->select();

        foreach ($attributes as $attributeCode) {
            $this->_joinAttribute(
                $select,
                '%1$s.entity_id = index.category_id and %1$s.attribute_id = %3$s and %1$s.store_id = %2$s',
                $attributeCode,
                Mage_Catalog_Model_Category::ENTITY,
                'index',
                'joinLeft'
            );
        }

        $select->joinLeft(
            array('rewrite' => $this->getTable('core/url_rewrite')),
            $this->_createCondition(
                $this->_quoteInto('rewrite.id_path LIKE ?', 'category/%'),
                'rewrite.category_id = index.category_id',
                'rewrite.store_id = index.store_id'
            ),
            array('request_path' => 'rewrite.request_path')
        );

        $select->columns(
            array('updated_at' => new Zend_Db_Expr($this->_quoteInto('?', Varien_Date::now())))
        );

        if ($limit !== null) {
            $select->where('index.category_id IN(?)', $limit);
        }

        $this->_getIndexAdapter()->query(
            $this->_getIndexAdapter()->updateFromSelect(
                $select, array('index' => $this->getMainTable())
            )
        );

        return $this;
    }
}
