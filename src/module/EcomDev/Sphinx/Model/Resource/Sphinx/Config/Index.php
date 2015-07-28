<?php

class EcomDev_Sphinx_Model_Resource_Sphinx_Config_Index
    extends Mage_Core_Model_Resource_Db_Abstract
{
    const INDEX_PRODUCT = EcomDev_Sphinx_Model_Sphinx_Config_Index::INDEX_PRODUCT;
    const INDEX_CATEGORY = EcomDev_Sphinx_Model_Sphinx_Config_Index::INDEX_CATEGORY;
    
    const INDEX_PRODUCT_ATTRIBUTE = 'product_attribute';
    const INDEX_PRODUCT_OPTION = 'product_option';
    


    /**
     * List of available tables
     * 
     * @var string[]
     */
    protected $_indexTables = array();
   
    protected function _construct()
    {
        $this->_init('ecomdev_sphinx/index_metadata', 'document_id');
        $this->_indexTables[self::INDEX_PRODUCT] = $this->getTable('ecomdev_sphinx/index_product');
        $this->_indexTables[self::INDEX_PRODUCT_ATTRIBUTE] = $this->getTable('ecomdev_sphinx/index_product_attribute');
        $this->_indexTables[self::INDEX_PRODUCT_OPTION] = $this->getTable('ecomdev_sphinx/index_product_option');
        $this->_indexTables[self::INDEX_CATEGORY] = $this->getTable('ecomdev_sphinx/index_category');
    }

    /**
     * Returns information if index is ready
     *
     * @return bool
     */
    public function isIndexReady()
    {
        return $this->_getReadAdapter()->isTableExists(
            $this->_indexTables[self::INDEX_PRODUCT_ATTRIBUTE]
        );
    }

    /**
     * Makes a kill list for a product
     *
     * @param string $timestampStart
     * @param string $timestampEnd
     * @return Varien_Db_Select
     */
    public function getKillListSelect($type, $timestampStart = '@ts_start', $timestampEnd = '@ts_end')
    {
        $selectDeleted = $this->_getReadAdapter()->select();
        $selectDeleted->from($this->getTable('ecomdev_sphinx/index_deleted'), 'document_id');
        $selectDeleted->where('type = ? ', $type);

        return (string)$selectDeleted;
    }

    /**
     * Returns select for index data
     *
     * @return string
     */
    public function getProductIndexDataSelect($isRange = true, $timestampStart = '@ts_start', $timestampEnd = '@ts_end')
    {
        $mainColumns = $this->_getReadAdapter()->describeTable(
            $this->_indexTables[self::INDEX_PRODUCT]
        );

        $attributeColumns = $this->_getReadAdapter()->describeTable(
            $this->_indexTables[self::INDEX_PRODUCT_ATTRIBUTE]
        );

        unset($attributeColumns['document_id']);
        unset($mainColumns['updated_at']);

        $selectColumns = array();

        foreach ($attributeColumns as $columnName => $column) {
            if (in_array($column['DATA_TYPE'], array(
                    Varien_Db_Ddl_Table::TYPE_DATE,
                    Varien_Db_Ddl_Table::TYPE_DATETIME))
            ) {
                $selectColumns[$columnName] = sprintf('UNIX_TIMESTAMP(attribute.%s)', $columnName);
            } else {
                $selectColumns[] = $columnName;
            }
        }

        $select = $this->_getIndexBaseSelect(self::INDEX_PRODUCT, $timestampStart, $timestampEnd);
        $select->columns(array_keys($mainColumns), 'main');
        $select->columns($selectColumns, 'attribute');

        if ($isRange) {
            $select->where('main.document_id >= $start');
            $select->where('main.document_id <= $end');
        }

        $select->where('main.store_id = ?', $this->getCurrentStoreId(self::INDEX_PRODUCT));
        $select->where('main.visibility IN(@visibility, ?)', Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH);

        return (string)$select;
    }

    /**
     * Returns select for index data
     *
     * @return string
     */
    public function getCategoryIndexDataSelect($isRange = true, $timestampStart = '@ts_start', $timestampEnd = '@ts_end')
    {
        $mainTable = $this->getTable('ecomdev_sphinx/index_category');

        $mainColumns = $this->_getReadAdapter()->describeTable(
            $mainTable
        );

        $select = $this->_getIndexBaseSelect(self::INDEX_CATEGORY, $timestampStart, $timestampEnd);
        $select->columns(array_keys($mainColumns), 'main');

        if ($isRange) {
            $select->where('main.document_id >= $start');
            $select->where('main.document_id <= $end');
        }

        $select->where('main.store_id = ?', $this->getCurrentStoreId(self::INDEX_CATEGORY));

        return (string)$select;
    }

    /**
     * @param string $timestampStart
     * @param string $timestampEnd
     * @return Varien_Db_Select
     */
    public function getIndexRangeSelect($type, $timestampStart = '@ts_start', $timestampEnd = '@ts_end')
    {
        $select = $this->_getIndexBaseSelect($type, $timestampStart, $timestampEnd);
        $select->columns(array('MIN(main.document_id)', 'MAX(main.document_id)'));
        $select->where('main.store_id = ?', $this->getCurrentStoreId($type));
        return (string)$select;
    }

    /**
     * Base select object for index
     *
     * @param string $timestampStart
     * @param string $timestampEnd
     * @return Varien_Db_Select
     */
    protected function _getIndexBaseSelect($type, $timestampStart = '@ts_start', $timestampEnd = '@ts_end')
    {
        if (isset($this->_indexTables[$type])) {
            $mainTable = $this->_indexTables[$type];
        } else {
            throw new RuntimeException('Unknown index type specified');
        }

        $select = $this->_getReadAdapter()->select();
        $select
            ->from(
                array('main' => $mainTable),
                array()
            );

        if ($timestampStart && $timestampEnd) {
            $select
                ->where('main.updated_at >= ' . $timestampStart)
                ->where('main.updated_at <= ' . $timestampEnd);
        }

        if ($type === self::INDEX_PRODUCT) {
            $select->where('main.status = ?', Mage_Catalog_Model_Product_Status::STATUS_ENABLED);
            $attributeTable = $this->_indexTables[self::INDEX_PRODUCT_ATTRIBUTE];
            $select->join(
                array('attribute' => $attributeTable),
                'main.document_id = attribute.document_id',
                array()
            );
        }

        return $select;
    }

    /**
     * Product category ids select
     *
     * @param bool $isDirect
     * @param bool $isRanged
     * @return Varien_Db_Select[]|Varien_Db_Select
     */
    public function getCategoryAttributeQuery(
        $isDirect = true,
        $isRanged = true,
        $asString = false)
    {
        $startExpr = $this->getCurrentIndexMetaSelect('previous_reindex_at');
        $endExpr = $this->getCurrentIndexMetaSelect('current_reindex_at');
        $categoryProductIndex = $this->getTable('catalog/category_product_index');
        $select = $this->_getIndexBaseSelect(self::INDEX_PRODUCT, $startExpr, $endExpr);
        $select
            ->join(
                array('category_product' => $categoryProductIndex),
                implode(' and ', array(
                    'category_product.product_id = main.product_id',
                    'category_product.store_id = main.store_id'
                )),
                array()
            );

        if ($isDirect) {
            $select->join(
                array('category_index' => $this->_indexTables[self::INDEX_CATEGORY]),
                implode(' and ', array(
                    'category_index.category_id = category_product.category_id',
                    'category_index.store_id = category_product.store_id'
                )),
                array()
            );

            $select->where('category_product.is_parent = ?', 0);
        }

        $categoryColumn = (
            $asString ? new Zend_Db_Expr("CONCAT('cat_', category_product.category_id)")
                : 'category_product.category_id'
        );

        $select->columns(array('document_id', 'category_id' => $categoryColumn), 'main');
        $select->order('main.document_id ASC');

        if ($isRanged) {
            $select->where('main.document_id >= $start');
            $select->where('main.document_id <= $end');
            return array((string)$select, (string)$this->getIndexRangeSelect(self::INDEX_PRODUCT, false, false));
        }

        return (string)$select;
    }

    /**
     * Base attribute select
     *
     * @param $attributeId
     * @param bool $isRanged
     * @return Varien_Db_Select[]|Varien_Db_Select
     */
    public function getOptionAttributeRangedQuery(
        $attributeId,
        $isRanged = true
    )
    {
        $startExpr = $this->getCurrentIndexMetaSelect('previous_reindex_at');
        $endExpr = $this->getCurrentIndexMetaSelect('current_reindex_at');

        $select = $this->_getOptionBaseQuery($attributeId, $startExpr, $endExpr);
        $select->columns(array('document_id', 'option_id'), 'option');
        $select->order('option.document_id ASC');

        if ($isRanged) {
            $select->where('option.document_id >= $start');
            $select->where('option.document_id <= $end');
            $rangeQuery = $this->_getOptionBaseQuery($attributeId, $startExpr, $endExpr);
            $rangeQuery->columns(
                array('MIN(option.document_id)', 'MAX(option.document_id)')
            );

            return array((string)$select, (string)$rangeQuery);
        }

        return (string)$select;
    }

    /**
     * Returns a value for a current index select
     *
     * @param string $field
     * @return string
     */
    public function getCurrentIndexMetaSelect($field, $storeAware = true)
    {
        $select = $this->_getReadAdapter()->select()
            ->from($this->getMainTable(), array());

        if ($storeAware) {
            $select->where(
                sprintf('code = CONCAT(?, (%s))', (string)$this->getCurrentStoreId('all')),
                'current_index_'
            );
            $columns = array($field);
        } else {
            $columns = array(new Zend_Db_Expr(sprintf('MAX(%s)', $field)));
            $select->where('code LIKE ?', 'current_index_%');
        }

        $select->columns($columns);
        return $this->_getReadAdapter()->quoteInto('?', $select);
    }

    /**
     * Base option field query
     *
     * @param $attributeId
     * @param bool $isRanged
     * @param string $timestampStart
     * @param string $timestampEnd
     * @return array|Varien_Db_Select
     */
    public function getOptionFieldRangedQuery(
        $attributeId,
        $isRanged = true,
        $timestampStart = '@ts_start',
        $timestampEnd = '@ts_end')
    {
        $select = $this->_getOptionBaseQuery($attributeId, $timestampStart, $timestampEnd);
        $select->columns(array('document_id', 'label'), 'option');
        $select->where('main.store_id = ?', $this->getCurrentStoreId(self::INDEX_PRODUCT));
        $select->order('option.document_id ASC');

        if ($isRanged) {
            $select->where('option.document_id >= $start');
            $select->where('option.document_id <= $end');
            $rangeQuery = $this->_getOptionBaseQuery($attributeId, $timestampStart, $timestampEnd);
            $rangeQuery->where('main.store_id = ?', $this->getCurrentStoreId(self::INDEX_PRODUCT));
            $rangeQuery->columns(
                array('MIN(option.document_id)', 'MAX(option.document_id)')
            );

            return array($select, $rangeQuery);
        }

        return $select;
    }

    /**
     * Returns an option base query
     *
     * @param int $attributeId
     * @param string $timestampStart
     * @param string $timestampEnd
     * @return Varien_Db_Select
     */
    protected function _getOptionBaseQuery($attributeId,
                                           $timestampStart = '@ts_start',
                                           $timestampEnd = '@ts_end')
    {
        $select = $this->_getReadAdapter()->select();
        $select
            ->from(
                array('option' => $this->_indexTables[self::INDEX_PRODUCT_OPTION]),
                array()
            )
            ->join(
                array('main' => $this->_indexTables[self::INDEX_PRODUCT]),
                $this->_getReadAdapter()->quoteInto(
                    'option.document_id = main.document_id',
                    $attributeId
                ),
                array()
            )
            ->where('option.attribute_id = ?', $attributeId)
            ->where('main.status <> ?', 0)
        ;

        if ($timestampStart && $timestampEnd) {
            $select->where('main.updated_at >= ' . $timestampStart)
                ->where('main.updated_at <= ' . $timestampEnd);
        }

        return $select;
    }

    /**
     * Returns pre index statements
     *
     * @param bool $isDelta
     * @param string $type
     * @return array
     */
    public function getPreIndexStatements($isDelta = false, $type = self::INDEX_PRODUCT, $storeId = 0, $visibility = null)
    {
        $isEnabled = $this->_getReadAdapter()->fetchOne('select @@query_cache_type');

        $statements = array(
            'SET NAMES utf8',
            'SET CHARACTER_SET_RESULTS=utf8'
        );

        if (!in_array($isEnabled, array('OFF', '0'))) {
            $statements[] = 'SET SESSION query_cache_type=OFF';
        }

        if ($visibility !== null) {
            $statements[] = $this->_getReadAdapter()->quoteInto('SET @visibility=?', $visibility);
        }

        $statements[] = $this->_getReadAdapter()->quoteInto('SET @storeId=?', $storeId);

        $tsStartExpr = $this->_getIndexBaseSelect($type, false, false)
            ->where('store_id = @storeId')
            ->columns(array(
                'MIN(updated_at)'
            ));

        $tsEndExpr = $this->_getIndexBaseSelect($type, false, false)
            ->where('store_id = @storeId')
            ->columns(array(
                'MAX(updated_at)'
            ));

        if ($isDelta) {
            $metaDataSelect = $this->_getReadAdapter()->select()
                ->from($this->getMainTable(), 'current_reindex_at')
                ->where('code = ?', $type);

            $tsStartExpr->where('updated_at > ?', $metaDataSelect);
        }

        $statements[] = $this->_getReadAdapter()->quoteInto('SET @ts_start:=?', $tsStartExpr);
        $statements[] = $this->_getReadAdapter()->quoteInto('SET @ts_end:=?', $tsEndExpr);
        $statements[] = sprintf(
            'REPLACE INTO %1$s (code, previous_reindex_at, current_reindex_at) '
            . ' VALUES (%2$s, @ts_start, @ts_end)',
            $this->getMainTable(),
            $this->_getReadAdapter()->quoteInto('?', sprintf('current_index_%s', $storeId))
        );

        $statements[] = sprintf(
            'REPLACE INTO %1$s (type, store_id) '
            . ' VALUES (%2$s, @storeId)',
            $this->getTable('ecomdev_sphinx/index_store'),
            $this->_getReadAdapter()->quoteInto('?', $type)
        );

        $statements[] = sprintf(
            'REPLACE INTO %1$s (type, store_id) '
            . ' VALUES (%2$s, @storeId)',
            $this->getTable('ecomdev_sphinx/index_store'),
            $this->_getReadAdapter()->quoteInto('?', 'all')
        );

        if (!$isDelta) {
            $statements[] = sprintf(
                'DELETE FROM %s WHERE %s = %s',
                $this->getTable('ecomdev_sphinx/index_deleted'),
                $this->_getReadAdapter()->quoteIdentifier('type'),
                $this->_getReadAdapter()->quoteInto('?', $type)
            );
        } else {
            $select = $this->_getIndexBaseSelect($type, '@ts_start', '@ts_end');
            $select
                ->columns(array(
                    'document_id' => 'main.document_id',
                    'type' => new Zend_Db_Expr($this->_getReadAdapter()->quote($type))
                ))
                ->where('main.store_id = @storeId');

            $statements[] = $this->_getReadAdapter()->insertFromSelect(
                $select, $this->getTable('ecomdev_sphinx/index_deleted'),
                array('document_id', 'type'),
                Varien_Db_Adapter_Interface::INSERT_IGNORE
            );
        }

        return $statements;
    }

    /**
     * Returns post index statements
     *
     * @param string $type
     * @return array
     */
    public function getPostIndexStatements($type = self::INDEX_PRODUCT)
    {
        return array(
            sprintf('SET @ts_start:=%s', $this->getCurrentIndexMetaSelect('previous_reindex_at', false)),
            sprintf('SET @ts_end:=%s', $this->getCurrentIndexMetaSelect('current_reindex_at', false)),
            sprintf(
                'INSERT INTO %1$s (code, previous_reindex_at, current_reindex_at)'
                . ' VALUES (%2$s, @ts_start, @ts_end)'
                . ' ON DUPLICATE KEY UPDATE'
                . ' %1$s.previous_reindex_at = %1$s.current_reindex_at,'
                . ' %1$s.current_reindex_at = VALUES(current_reindex_at)',
                $this->getMainTable(),
                $this->_getReadAdapter()->quoteInto('?', $type)
            ),
            $this->_getReadAdapter()->quoteInto(
                sprintf('DELETE FROM %1$s WHERE code LIKE ?', $this->getMainTable()),
                'current_index_%'
            )
        );
    }


    /**
     * Returns a database connection details
     *
     * @return stdClass
     */
    public function getDbConnection()
    {
        $config = $this->_getWriteAdapter()->getConfig();
        $connection = new stdClass();

        if (isset($config['unix_socket'])) {
            $connection->sock = $config['unix_socket'];
        } else {
            $connection->host = $config['host'];
            if (isset($config['port'])) {
                $connection->port = $config['port'];
            } else {
                $connection->port = 3306;
            }
        }

        $connection->user = $config['username'];
        $connection->pass = $config['password'];
        $connection->db = $config['dbname'];
        return $connection;
    }

    /**
     * Returns amount of pending rows for index
     * 
     * @param $index
     * @return mixed
     */
    public function getPendingRowCount($index)
    {
        $metaDataSelect = $this->_getReadAdapter()->select()
            ->from($this->getMainTable(), 'current_reindex_at')
            ->where('code = ?', $index);

        $select = $this->_getIndexBaseSelect($index, false, false)
            ->columns(array('COUNT(main.document_id)'))
            ->where('main.updated_at > ?', $metaDataSelect);
        ;
        
        return $this->_getReadAdapter()->fetchOne($select);
    }

    /**
     * Returns amount of indexed rows for index
     *
     * @param $index
     * @return mixed
     */
    public function getIndexedRowCount($index)
    {
        $metaDataSelect = $this->_getReadAdapter()->select()
            ->from($this->getMainTable(), 'current_reindex_at')
            ->where('code = ?', $index);

        $select = $this->_getIndexBaseSelect($index, false, false)
            ->columns(array('COUNT(main.document_id)'))
            ->where('main.updated_at <= ?', $metaDataSelect);
        ;

        return $this->_getReadAdapter()->fetchOne($select);
    }

    /**
     * Current store id select
     *
     * @param string $type
     * @return Varien_Db_Select
     */
    protected function getCurrentStoreId($type)
    {
        return $this->_getReadAdapter()->select()
            ->from($this->getTable('ecomdev_sphinx/index_store'), 'store_id')
            ->where('type = ?', $type);
    }
}
