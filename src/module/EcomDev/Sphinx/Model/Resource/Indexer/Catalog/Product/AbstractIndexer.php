<?php

use EcomDev_Sphinx_Model_Resource_Indexer_Catalog_Product as ProductIndexer;
use EcomDev_Sphinx_Model_Config as ConfigInstance;

/**
 * Abstract indexer class
 * 
 */
abstract class EcomDev_Sphinx_Model_Resource_Indexer_Catalog_Product_AbstractIndexer
    extends EcomDev_Sphinx_Model_Resource_Indexer_Catalog_AbstractIndexer
    implements EcomDev_Sphinx_Model_Resource_Indexer_Catalog_Product_IndexerInterface
{
    
    /**
     * Product indexer instance
     * 
     * @var ProductIndexer
     */
    protected $_indexer;

    /**
     * Instance of product configuration
     * 
     * @var ConfigInstance
     */
    protected $_config;
    
    /**
     * Set an indexer instance
     *
     * @param ProductIndexer $indexer
     * @return $this
     */
    public function setIndexer(ProductIndexer $indexer)
    {
        $this->_indexer = $indexer;
        return $this;
    }

    /**
     * Return an indexer instance
     *
     * @return ProductIndexer
     */
    public function getIndexer()
    {
        return $this->_indexer;
    }

    /**
     * Sets configuration instance for an indexer
     *
     * @param ConfigInstance $config
     * @return $this
     */
    public function setConfig(ConfigInstance $config)
    {
        $this->_config = $config;
        return $this;
    }

    /**
     * Returns an instance of configuration
     *
     * @return ConfigInstance
     */
    public function getConfig()
    {
        return $this->_config;
    }

    /**
     * Re-indexes data for products
     * Returns number of changed rows
     *
     * @param Varien_Db_Select|null $limit
     * @return int
     */
    public function reindexData(Varien_Db_Select $limit = null)
    {
        $this->useIdxTable($limit === null);
        $this->clearTemporaryIndexTable();
        $this->_reindexData($limit);
        return $this->_syncIndexData($limit);
    }

    /**
     * Re-indexes index data
     * 
     * @param Varien_Db_Select $limit
     * @return $this
     */
    abstract protected function _reindexData(Varien_Db_Select $limit = null);

    /**
     * Returns number of changed records in index after re-indexation
     * 
     * @param Varien_Db_Select $limit
     * @return int
     */
    protected function _syncIndexData(Varien_Db_Select $limit = null)
    {
        return $this->_executeQueries(array(
            $this->_syncRemovedRecords($limit),
            $this->_syncNewRecordsAndUpdates()
        ));
    }

    /**
     * Generates sql for removal of records from index that does not satisfy new index data
     * 
     * @param Varien_Db_Select $limit
     * @return string
     */
    protected function _syncRemovedRecords(Varien_Db_Select $limit = null)
    {
        $columns = $this->_getIndexAdapter()->describeTable($this->getMainTable());
        $primaryCondition = array();
        foreach ($columns as $name => $column) {
            if (!empty($column['PRIMARY'])) {
                $primaryCondition[] = sprintf('tmp_index.%1$s = index.%1$s', $name);
            }
        }
        
        $select = $this->_getIndexAdapter()->select();
        $select
            ->from(
                array('index' => $this->getMainTable()),
                array()
            )
            ->join(
                array('main_index' => $this->getIndexer()->getMainTable()),
                'main_index.document_id = index.document_id',
                array()
            )
            ->joinLeft(
                array('tmp_index' => $this->getIdxTable()), 
                implode(' and ', $primaryCondition),
                array()
            )
            ->where('tmp_index.document_id IS NULL')
        ;
            
        if ($limit !== null) {
            $select->where('main_index.product_id IN(?)', $limit);
        }
        
        
        return $this->_getIndexAdapter()->deleteFromSelect($select, 'index');
    }

    /**
     * Generate sql statement for insert of new records from tmp index and updates existing
     *
     * @return string
     */
    protected function _syncNewRecordsAndUpdates()
    {
        $columns = $this->_getIndexAdapter()->describeTable($this->getMainTable());
        $updateColumns = array();
        $selectColumns = array();
        foreach ($columns as $name => $column) {
            $selectColumns[] = $name;
            if (empty($column['PRIMARY'])) {
                $updateColumns[] = $name;
            }
        }
        
        $select = $this->_getIndexAdapter()->select();
        $select->from($this->getIdxTable(), $selectColumns);
        
        return $this->insertFromSelectInternal(
            $select, $this->getMainTable(),
            $selectColumns, $updateColumns, 
            Varien_Db_Adapter_Interface::INSERT_ON_DUPLICATE
        );
    }

    /**
     * Creates a base attribute value select
     * 
     * @param string $table
     * @param string[] $attributeIds
     * @param string $joinCondition
     * @param Varien_Db_Select $limit
     * @return Varien_Db_Select
     */
    protected function _getBaseAttributeDataSelect($table,
                                                   $attributeIds,
                                                   $joinCondition,
                                                   Varien_Db_Select $limit = null)
    {
        $select = $this->_getIndexAdapter()->select();
        $select
            ->from(
                array('index' => $this->getIndexer()->getMainTable()),
                array()
            )
            ->join(
                array('attribute' => $table),
                $this->_createCondition(
                    'attribute.entity_id = index.product_id',
                    $joinCondition
                ),
                array()
            )
            ->where('attribute.attribute_id IN(?)', $attributeIds);
        ;

        if ($limit !== null) {
            $select->where('index.product_id IN(?)', $limit);
        }

        return $select;
    }

    /**
     * Inserts attribute values and removes inconsistent store values
     *
     * @param string $attributeValueTable
     * @param array $attributeIds
     * @param Closure $insertCallback
     * @param Varien_Db_Select $limit
     * @return int
     */
    protected function _insertAttributeValues($attributeValueTable, array $attributeIds,
                                              Closure $insertCallback, Varien_Db_Select $limit = null)
    {
        if (!$attributeIds) {
            return 0;
        }

        /** @var Varien_Db_Select[] $selects */
        $selects = array(
            'default' => $this->_quoteInto('attribute.store_id = ?', 0),
            'store' => 'attribute.store_id = index.store_id'
        );

        foreach ($selects as $code => $condition) {
            $selects[$code] = $this->_getBaseAttributeDataSelect(
                $attributeValueTable,
                $attributeIds,
                $condition,
                $limit
            );
        }
        
        $selects['default']
            ->joinLeft(
                array('attribute_store' => $attributeValueTable),
                $this->_createCondition(
                    'attribute_store.entity_id = index.product_id',
                    'attribute_store.store_id = index.store_id',
                    'attribute_store.attribute_id = attribute.attribute_id'
                ),
                array()
            )
            ->where('attribute_store.value_id IS NULL')
        ;

        return $this->_executeQueries(
            array(
                $insertCallback($selects['default']),
                $insertCallback($selects['store'])
            )
        );
    }
    
    /**
     * Returns insert into ignore statement based on select
     * 
     * @param Varien_Db_Select $select
     * @param string[] $columns
     * @return string
     */
    protected function _insertIgnoreIntoIndex($select, $columns)
    {
        return $this->insertFromSelectInternal(
            $select,
            $this->getIdxTable(),
            array_keys($columns),
            array(),
            Varien_Db_Adapter_Interface::INSERT_IGNORE
        );
    }

    /**
     * Return list of attribute ids by backend type from list of attributes
     *
     * @param EcomDev_Sphinx_Model_Attribute[] $attributes
     * @return string[]
     */
    protected function _getAttributeIds(array $attributes)
    {
        $attributeIds = array();
        $excludedAttributes = Mage::getResourceSingleton('ecomdev_sphinx/attribute')->getSystemAttributes();
        foreach ($attributes as $attribute) {
            if (in_array($attribute->getAttributeCode(), $excludedAttributes, true)) {
                continue;
            }
            $attributeIds[] = $attribute->getId();
        }

        return $attributeIds;
    }
}
