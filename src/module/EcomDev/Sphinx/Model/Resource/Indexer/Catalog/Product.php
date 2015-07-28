<?php

use EcomDev_Sphinx_Model_Indexer_Catalog_Product as ProductIndexer;

class EcomDev_Sphinx_Model_Resource_Indexer_Catalog_Product
    extends EcomDev_Sphinx_Model_Resource_Indexer_Catalog_AbstractIndexer
{
    const MAX_JOIN_SIZE = 59;
    
    /**
     * Main product attributes
     * 
     * @var string[]
     */
    protected $_mainAttributes = array(
        'status', 'name', 'short_description', 'description', 'tax_class_id'
    );

    /**
     * Indexers of product data
     * 
     * @var EcomDev_Sphinx_Model_Resource_Indexer_Catalog_Product_IndexerInterface[]
     */
    protected $_indexers = array();
    
    /**
     * Resource initialization
     *
     */
    protected function _construct()
    {
        $this->_init('ecomdev_sphinx/index_product', 'document_id');
        foreach($this->_getConfig()->getProductIndexerClasses() as $code => $indexClass) {
            $this->_indexers[$code] = new $indexClass; 
        }
    }

    /**
     * Reindex data by event
     * 
     * @param Mage_Index_Model_Event $event
     * @return EcomDev_Sphinx_Model_Resource_Indexer_Catalog_Product
     */
    public function catalogProductSave(Mage_Index_Model_Event $event)
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
        $this->_createIndexTrigger(
            $this->getMainTable(),
            EcomDev_Sphinx_Model_Sphinx_Config_Index::INDEX_PRODUCT,
            'document_id'
        );
        $this->_rebuildAttributeTable();
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
        if (isset($data[ProductIndexer::EVENT_PRODUCT_ID])) {
            $productIds = array($data[ProductIndexer::EVENT_PRODUCT_ID]);
        } elseif (isset($data[ProductIndexer::EVENT_PRODUCT_IDS])) {
            $productIds = array($data[ProductIndexer::EVENT_PRODUCT_IDS]);
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
            
            $this->_updateStockStatus($limit);
            $this->_updateCategoryNames($limit);
            $this->_updateRequestPath($limit);
            $this->_runAttributeIndexer($limit);
            $this->_updateAttributeTable($limit);
        }, $limit !== null, $limit);
        
        return $this;
    }
    
    protected function _updateAttributeTable(Varien_Db_Select $limit = null)
    {
        $attributeTable = $this->getTable('ecomdev_sphinx/index_product_attribute');
        if (!$this->_getIndexAdapter()->isTableExists($attributeTable)) {
            return $this;
        }

        $columns = $this->_getIndexAdapter()->describeTable($attributeTable);
        $plainAttributes = array_intersect_key(
            $this->_getConfig()->getPlainAttributes(),
            $columns
        );
        
        $select = $this->_getIndexAdapter()->select()
            ->from(array('index' => $this->getMainTable()), 'document_id');
        if ($limit !== null) {
            $select->where('index.product_id IN(?)', $limit);
        }
        
        $queries = array();
        
        $queries[] = $this->_getIndexAdapter()->insertFromSelect(
            $select, $attributeTable, array('document_id'), Varien_Db_Adapter_Interface::INSERT_IGNORE
        );
        
        foreach (array_chunk($plainAttributes, self::MAX_JOIN_SIZE, true) as $attributes) {
            $attributeSelect = $this->_getIndexAdapter()->select();
            $attributeSelect->join(
                array('main_index' => $this->getMainTable()),
                'main_index.document_id = index.document_id',
                array()
            );
            if ($limit !== null) {
                $attributeSelect->where('main_index.product_id IN(?)', $limit);
            }
            
            foreach ($attributes as $code => $attribute) {
                $tableAlias = $code . '_table';
                $attributeSelect->joinLeft(
                    array($tableAlias => $attribute->getIndexTable()),
                    sprintf($this->_createCondition(
                        '%1$s.document_id = index.document_id',
                        $this->_quoteInto('%1$s.attribute_id = ?', $attribute->getId())
                    ), $tableAlias),
                    array($code => 'value')
                );
            }
            
            $queries[] = $this->_getIndexAdapter()->updateFromSelect(
                $attributeSelect, array('index' => $attributeTable)
            );
        }
        
        $priceGroups = $this->_getPriceColumns(true);

        foreach (array_chunk($priceGroups, self::MAX_JOIN_SIZE, true) as $priceGroup) {
            $priceSelect = $this->_getIndexAdapter()->select();
            $priceSelect->join(
                array('main_index' => $this->getMainTable()),
                'main_index.document_id = index.document_id',
                array()
            );
            
            if ($limit !== null) {
                $priceSelect->where('main_index.product_id IN(?)', $limit);
            }
            
            $addQuery = false;
            foreach ($priceGroup as $customerGroupId => $priceColumns) {
                $priceColumns = array_intersect_key($priceColumns, $columns);
                if (!$priceColumns) {
                    continue;
                }
                $addQuery = true;

                $tableAlias = 'price_index_group_' . $customerGroupId;
                
                $priceSelect
                    ->joinLeft(
                        array($tableAlias => $this->getTable('ecomdev_sphinx/index_product_price')),
                        sprintf($this->_createCondition(
                            '%1$s.document_id = index.document_id',
                            $this->_quoteInto('%1$s.customer_group_id = ?', $customerGroupId)
                        ), $tableAlias),
                        array()
                    );
                
                foreach ($priceColumns as $destination => $source) {
                    $priceSelect->columns(array($destination => $tableAlias . '.' . $source));
                }
            }
            
            if ($addQuery) {
                $queries[] = $this->_getIndexAdapter()->updateFromSelect(
                    $priceSelect, array('index' => $attributeTable)
                );
            }
        }

        $this->_executeQueries($queries);
        return $this;
    }
    
    protected function _rebuildAttributeTable()
    {
        $attributeTable = $this->getTable('ecomdev_sphinx/index_product_attribute');
        $createTable = true;
        $plainAttributes = $this->_getConfig()->getPlainAttributes();
        
        foreach ($this->_getPriceColumns() as $column) {
            $plainAttributes[$column] = $this->_getConfig()->getAttributeByCode('price');
        }
        
        if ($this->_getIndexAdapter()->isTableExists($attributeTable)) {
            $this->_getIndexAdapter()->resetDdlCache($attributeTable);
            $columns = $this->_getIndexAdapter()->describeTable($attributeTable);
            if (array_diff_key($plainAttributes, $columns)) {
                $this->_getIndexAdapter()->dropTable($attributeTable);
            } else {
                $createTable = false;
                $this->_getIndexAdapter()->truncateTable($attributeTable);
            }
        }
        
        if ($createTable) {
            $this->_createAttributeTable($plainAttributes);
        }
        
        return $this;
    }

    /**
     * Returns price columns for index
     * 
     * @param bool $byGroup
     * @return string[]
     */
    public function _getPriceColumns($byGroup = false)
    {
        return $this->_getConfig()->getPriceColumns($byGroup);
    }
    
    /**
     * Updates stock status information
     * 
     * @param Varien_Db_Select $limit
     * @return $this
     */
    protected function _updateStockStatus(Varien_Db_Select $limit = null)
    {
        $select = $this->_getIndexAdapter()->select();
        $select->join(
            array('stock_status' => $this->getTable('cataloginventory/stock_status')),
            'stock_status.product_id = index.product_id',
            array(
                'stock_status' => 'stock_status.stock_status',
                'updated_at' => $this->_getUpdatedAtExpression(
                    'index.stock_status', 'stock_status.stock_status'
                )
            )
        );

        $this->_updateIndexTableFromSelect($select, $limit);
        return $this;
    }

    /**
     * Updates index from passed select and applies limit if there is any
     * 
     * @param Varien_Db_Select $select
     * @param Varien_Db_Select $limit
     * @return $this
     */
    protected function _updateIndexTableFromSelect(
        Varien_Db_Select $select, 
        Varien_Db_Select $limit = null,
        $table = null
    )
    {
        if ($limit !== null) {
            $select->where('index.product_id IN(?)', $limit);
        }

        if ($table === null) {
            $table = array('index' => $this->getMainTable());
        }
        
        $this->_getIndexAdapter()->query(
            $this->_getIndexAdapter()->updateFromSelect(
                $select, $table
            )
        );
        
        return $this;
    }
    
    /**
     * Updates category name
     * 
     * @param Varien_Db_Select $limit
     * @return $this
     */
    protected function _updateCategoryNames(Varien_Db_Select $limit = null)
    {
        $select = $this->_getIndexAdapter()->select();
        $select
            ->join(
                array('product_category' => $this->getTable('catalog/category_product')),
                'product_category.product_id = index.product_id',
                array()
            )
            ->join(
                array('category_index' => $this->getTable('ecomdev_sphinx/index_category')),
                $this->_createCondition(
                    'category_index.category_id = product_category.category_id',
                    'category_index.store_id = index.store_id'
                ),
                array(
                    'category_names' => $this->_getIndexAdapter()->getCheckSql(
                        $this->_quoteInto('index.category_names <> ?', ''),
                        $this->_getIndexAdapter()->getConcatSql(
                            array('index.category_names', 'category_index.name'),
                            ', '
                        ),
                        'category_index.name'
                    )
                )
            )
        ;
        
        Mage::dispatchEvent('ecomdev_sphinx_indexer_product_category_names', array(
            'select' => $select,
            'limit' => $limit,
            'indexer' => $this
        ));
        
        $where = '';
        if ($limit !== null) {
            $where = array('product_id IN(?)' => $limit);
        }
        
        // Clean up category names for product
        $this->_getIndexAdapter()->update(
            $this->getMainTable(), 
            array('category_names' => ''),
            $where
        );
        
        $this->_updateIndexTableFromSelect($select, $limit);
        return $this;
    }

    /**
     * Update request path
     * 
     * @param Varien_Db_Select $limit
     * @return $this
     */
    protected function _updateRequestPath(Varien_Db_Select $limit = null)
    {
        $select = $this->_getIndexAdapter()->select();
        $select
            ->join(
                array('rewrite' => $this->getTable('core/url_rewrite')),
                $this->_createCondition(
                    $this->_quoteInto('rewrite.id_path LIKE ?', 'product/%'),
                    'rewrite.product_id = index.product_id',
                    'rewrite.category_id IS NULL',
                    'rewrite.store_id = index.store_id'
                ),
                array(
                    'request_path' => 'rewrite.request_path',
                    'updated_at' => $this->_getUpdatedAtExpression(
                        'index.request_path', 'rewrite.request_path'
                    )
                )
            );
        
        $this->_updateIndexTableFromSelect($select, $limit);
        return $this;
    }
    
    /**
     * Runs single attribute indexers
     * 
     * @param Varien_Db_Select $limit
     * @return $this
     */
    protected function _runAttributeIndexer(Varien_Db_Select $limit = null)
    {
        $affectedRows = 0;
        
        foreach ($this->_indexers as $indexer) {
            $indexer->setIndexer($this)
                ->setConfig($this->_getConfig());
            $affectedRows += $indexer->reindexData($limit);
        }
        
        // In case if any rows have been updated, we mark all products as updated
        if ($affectedRows > 0 && $limit != null) {
            $this->_getIndexAdapter()->update(
                $this->getMainTable(), 
                array('updated_at' => Varien_Date::now()),
                array('product_id IN(?)' => $limit)
            );
        }
        
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
        
        $attributeExpression = $this->_getAttributeIfNullExpr($attributeCode);
        
        $updatedAt = $this->_getUpdatedAtExpression(
            'index.' . $attributeCode, $attributeExpression
        );
        
        $select->columns(array('updated_at' => $updatedAt));

        $this->_updateIndexTableFromSelect($select, $limit);
        
        return $this;
    }

    /**
     * Updated at field expression for cross updates
     * 
     * @param string $originalValue
     * @param string $newValue
     * @param string $updatedAtField
     * @return Zend_Db_Expr
     */
    protected function _getUpdatedAtExpression($originalValue, $newValue, $updatedAtField = 'index.updated_at')
    {   
        return $this->_getIndexAdapter()->getCheckSql(
            $originalValue . ' <> ' . $newValue,
            $this->_quoteInto('?', Varien_Date::now()),
            $updatedAtField
        );
    }

    /**
     * Inserts main rows
     * 
     * @param Varien_Db_Select $limit
     * @return string
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
                array('store_group' => $this->getTable('core/store_group')),
                'store_group.root_category_id = product_index.category_id',
                array()
            )
            ->join(
                array('store' => $this->getTable('core/store')),
                $this->_createCondition(
                    'store.store_id = product_index.store_id',
                    'store.group_id = store_group.group_id'
                ),
                array()
            )
            ->join(
                array('product' => $this->getTable('catalog/product')),
                'product_index.product_id = product.entity_id',
                array()
            )
        ;
        
        $columns = array(
            'product_id' => 'product_index.product_id',
            'store_id' => 'product_index.store_id',
            'type_id' => 'product.type_id',
            'visibility' => 'product_index.visibility',
            'sku' => 'product.sku',
            'attribute_set_id' => 'product.attribute_set_id',
            'has_options' => 'product.has_options',
            'required_options' => 'product.required_options',
            'updated_at' => new Zend_Db_Expr(
                $this->_quoteInto('?', Varien_Date::now())
            ),
        );
        
        $select->columns($columns);
        
        if ($limit !== null) {
            $select->where('product_index.product_id IN(?)', $limit);
        }
        
        $tableName = $this->getMainTable();
        
        $this->_getIndexAdapter()->query(
            $this->insertFromSelectInternal(
                $select, 
                $this->getMainTable(), 
                array_keys($columns),
                array(
                    'visibility',
                    'sku',
                    'updated_at' => $this->_getIndexAdapter()->getCheckSql(
                        $this->_createCondition(
                            $tableName . '.sku <> VALUES(sku)',
                            $tableName . '.visibility <> VALUES(visibility)',
                            $tableName . '.attribute_set_id <> VALUES(attribute_set_id)',
                            $tableName . '.has_options <> VALUES(has_options)',
                            $tableName . '.required_options <> VALUES(required_options)',
                            'or'
                        ),
                        'VALUES(updated_at)',
                        $tableName . '.updated_at'
                    )
                ),
                Varien_Db_Adapter_Interface::INSERT_ON_DUPLICATE
            )
        );
        
        return $this;
    }

    /**
     * @param EcomDev_Sphinx_Model_Attribute[] $plainAttributes
     * @return $this
     */
    protected function _createAttributeTable(array $plainAttributes)
    {
        $table = $this->_getIndexAdapter()->newTable(
            $this->getTable('ecomdev_sphinx/index_product_attribute')
        );
        
        $table->addColumn(
            'document_id', 
            Varien_Db_Ddl_Table::TYPE_INTEGER, 
            null, 
            array('primary' => true)
        );
        
        foreach ($plainAttributes as $code => $attribute) {
            $type = null;
            $size = null;
            switch ($attribute->getBackendType()) {
                case 'int';
                    $type = Varien_Db_Ddl_Table::TYPE_INTEGER;
                    break;
                case 'decimal':
                    $type = Varien_Db_Ddl_Table::TYPE_DECIMAL;
                    $size = array(12, 4);
                    break;
                case 'text':
                    $type = Varien_Db_Ddl_Table::TYPE_TEXT;
                    $size = '512k';
                    break;
                case 'varchar':
                    $type = Varien_Db_Ddl_Table::TYPE_TEXT;
                    $size = 255;
                    break;
                case 'datetime':
                    $type = Varien_Db_Ddl_Table::TYPE_DATETIME;
                    break;
            }
            
            $table->addColumn(
                $code, 
                $type, 
                $size
            );
        }
        
        $this->_getIndexAdapter()->createTable($table);
        $this->_getIndexAdapter()->resetDdlCache($table->getName());
        return $this;
    }
}
