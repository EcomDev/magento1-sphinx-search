<?php

use EcomDev_Sphinx_Model_Indexer_Catalog_Product as ProductIndexer;

class EcomDev_Sphinx_Model_Resource_Indexer_Catalog_Product
    extends EcomDev_Sphinx_Model_Resource_Indexer_Catalog_AbstractIndexer
{
    /**
     * Main product attributes
     * 
     * @var string[]
     */
    protected $_mainAttributes = array(
        'status', 'visibility'
    );

    /** @var EcomDev_Sphinx_Model_Resource_Trigger */
    private $trigger;
    
    /**
     * Resource initialization
     *
     */
    protected function _construct()
    {
        $this->_init('ecomdev_sphinx/index_product', 'product_id');
        $this->trigger = Mage::getResourceSingleton('ecomdev_sphinx/trigger');
    }

    /**
     * Reindex data by event
     * 
     * @param Mage_Index_Model_Event $event
     * @return $this
     */
    public function sphinxUpdateMassAction(Mage_Index_Model_Event $event)
    {
        return $this->_reindexByEvent($event);
    }

    /**
     * Re-indexes all entries for sphinx
     * 
     * @return $this
     */
    public function reindexAll()
    {
        $this->trigger->validateTriggers();
        $this->_reindexProducts();
        return $this;
    }


    /**
     * Reindexes data by event from indexer
     * 
     * @param Mage_Index_Model_Event $event
     * @return $this
     */
    protected function _reindexByEvent(Mage_Index_Model_Event $event)
    {
        $data = $event->getNewData();

        if (isset($data[ProductIndexer::EVENT_PRODUCT_IDS])) {
            $productIds = $data[ProductIndexer::EVENT_PRODUCT_IDS];
        } else {
            return $this;
        }

        $limit = $this->_getAllPossibleProducts($productIds);
        $this->_reindexProducts($limit);
        return $this;
    }

    /**
     * Returns a limitation select for product ids
     * 
     * @param array $productIds
     * @return Varien_Db_Select
     */
    protected function _getAllPossibleProducts(array $productIds)
    {
        $mainSelect = $this->_getIndexAdapter()->select();
        $mainSelect
            ->from(array('product' => $this->getTable('catalog/product')), array('entity_id'))
            ->where('product.entity_id IN(?)', $productIds);
        
        $parentSelect = $this->_getIndexAdapter()->select();
        $parentSelect
            ->from(array('relation' => $this->getTable('catalog/product_relation')), array('parent_id'))
            ->where('relation.child_id IN(?)', $productIds);
        
        $unionSelect = $this->_getIndexAdapter()->select();
        $unionSelect->union(
            array($mainSelect, $parentSelect),
            Varien_Db_Select::SQL_UNION_ALL
        );

        Mage::dispatchEvent(
            'ecomdev_sphinx_indexer_product_limit',
            array(
                'select' => $unionSelect, 
                'product_ids' => $productIds
            )
        );
        
        return $unionSelect;
    }

    /**
     * Limitation of products
     *
     * @param Varien_Db_Select $limit
     * @return $this
     */
    protected function _reindexProducts(Varien_Db_Select $limit = null)
    {
        $this->_transactional(function ($limit) {
            $this->_insertMainRows($limit);

            foreach ($this->_mainAttributes as $attribute) {
                $this->_updateProductMainAttribute($attribute, $limit);
            }

            $this->_removeInvalidRows($limit);

            $condition = '';

            if ($limit !== null) {
                $condition = [
                    'product_id IN(?)' => $limit
                ];
            }

            $this->_getIndexAdapter()->update(
                $this->getMainTable(),
                array(
                    'updated_at' => Varien_Date::now()
                ),
                $condition
            );
        }, $limit !== null, $limit);
        
        return $this;
    }

    /**
     * Updates main attribute data for index
     *
     * @param $attributeCode
     * @param Varien_Db_Select $limit
     * @return $this
     */
    protected function _updateProductMainAttribute($attributeCode, Varien_Db_Select $limit = null)
    {
        $select = $this->_getIndexAdapter()->select();

        $this->_joinAttribute(
            $select,
            '%1$s.entity_id = index.product_id and %1$s.attribute_id = %3$s and %1$s.store_id = %2$s',
            $attributeCode,
            Mage_Catalog_Model_Product::ENTITY,
            'index'
        );

        $this->_updateIndexTableFromSelect($select, $limit);
        return $this;
    }

    /**
     * Inserts main rows
     * 
     * @param Varien_Db_Select $limit
     * @return $this
     */
    protected function _insertMainRows(Varien_Db_Select $limit = null)
    {
        $select = $this->_getIndexAdapter()->select();
        $select
            ->from(
                array('product_index' => $this->getTable('catalog/category_product_index')),
                array()
            )
            ->join(
                array('store' => $this->getTable('core/store')),
                'store.store_id = product_index.store_id',
                array()
            )
            ->where('store.store_id <> ?', 0)
        ;
        
        $columns = array(
            'product_id' => 'product_index.product_id',
            'store_id' => 'product_index.store_id',
            'visibility' => 'product_index.visibility',
            'updated_at' => new Zend_Db_Expr($this->_getIndexAdapter()->quote(Varien_date::now()))
        );
        
        $select->columns($columns);
        
        if ($limit !== null) {
            $select->where('product_index.product_id IN(?)', $limit);
        }

        $this->insertFromSelect(
            $select,
            $this->getMainTable(),
            array_keys($columns)
        );

        return $this;
    }

    /**
     * Inserts main rows
     *
     * @param Varien_Db_Select $limit
     * @return $this
     */
    protected function _removeInvalidRows(Varien_Db_Select $limit = null)
    {
        $conditions = [];
        $conditions['status = ?'] = Mage_Catalog_Model_Product_Status::STATUS_DISABLED;

        if ($limit !== null) {
            $conditions['product_id IN(?)'] = $limit;
        }

        $this->_getIndexAdapter()->delete(
            $this->getMainTable(),
            $conditions
        );

        return $this;
    }
}
