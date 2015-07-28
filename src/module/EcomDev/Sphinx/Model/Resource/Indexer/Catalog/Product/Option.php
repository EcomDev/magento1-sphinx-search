<?php

/**
 * Indexer for a product option sub indexer
 * 
 * 
 */
class EcomDev_Sphinx_Model_Resource_Indexer_Catalog_Product_Option
    extends EcomDev_Sphinx_Model_Resource_Indexer_Catalog_Product_AbstractIndexer
{

    /**
     * Resource initialization
     */
    protected function _construct()
    {
        $this->_init('ecomdev_sphinx/index_product_option', 'document_id');
    }

    /**
     * Re-indexes data for products
     * Returns number of changed rows
     *
     * @param Varien_Db_Select $limit
     * @return $this
     */
    protected function _reindexData(Varien_Db_Select $limit = null)
    {
        $optionAttributes = $this->_getConfig()->getAttributesByType('option');
        $this->_insertSimpleOption($optionAttributes, $limit);
        $this->_insertMultipleOption($optionAttributes, $limit);
        $this->_insertConfigurableOptions($limit);
        $this->_updateOptionLabel();
        return $this;
    }
    
    /**
     * Insert simple options for layered options
     * 
     * @param EcomDev_Sphinx_Model_Attribute[] $attributes
     * @param Varien_Db_Select $limit
     * @return $this
     */
    protected function _insertSimpleOption(array $attributes, Varien_Db_Select $limit = null)
    {
        $this->_insertOptionValues(
            $attributes, 
            'int', 
            function (Varien_Db_Select $select) {
                $selectColumns = array(
                    'document_id' => 'index.document_id',
                    'attribute_id' => 'attribute.attribute_id',
                    'option_id' => 'attribute.value'
                );
    
                $select->columns($selectColumns);
    
                return $this->_insertIgnoreIntoIndex($select, $selectColumns);
            }, 
            $limit
        );
        
        return $this;
    }

    /**
     * Inserts option values
     * 
     * @param array $attributes
     * @param string $type
     * @param Closure $insertCallback
     * @param Varien_Db_Select $limit
     * @return $this
     */
    protected function _insertOptionValues(array $attributes, $type,
                                           Closure $insertCallback, Varien_Db_Select $limit = null) 
    {
        $attributeValueTable = $this->getValueTable('catalog/product', $type);
        $attributeIds = $this->_getAttributeIdsByType($attributes, $type);
        $this->_insertAttributeValues($attributeValueTable, $attributeIds, $insertCallback, $limit);
        return $this;
    }

    /**
     * Insert simple options for layered options
     *
     * @param EcomDev_Sphinx_Model_Attribute[] $attributes
     * @param Varien_Db_Select $limit
     * @return $this
     */
    protected function _insertMultipleOption(array $attributes, Varien_Db_Select $limit = null)
    {
        $this->_insertOptionValues(
            $attributes, 
            'varchar', 
            function (Varien_Db_Select $select) {
                $selectColumns = array(
                    'document_id' => 'index.document_id',
                    'attribute_id' => 'attribute.attribute_id',
                    'option_id' => 'option.option_id'
                );
    
                $select->columns($selectColumns);
                $select->joinInner(
                    array('option' => $this->getTable('eav/attribute_option')),
                    $this->_createCondition(
                        'option.attribute_id = attribute.attribute_id'
                    ),
                    array()
                );
    
                $select->where(
                    'FIND_IN_SET(option.option_id, attribute.value)'
                );
    
                return $this->_insertIgnoreIntoIndex($select, $selectColumns);
            },
            $limit
        );
        
        return $this;
    }
    
    /**
     * Return list of attribute ids by backend type from list of attributes
     * 
     * @param EcomDev_Sphinx_Model_Attribute[] $attributes
     * @param string $type
     * @return string[]
     */
    protected function _getAttributeIdsByType(array $attributes, $type)
    {
        $attributeIds = array();
        foreach ($attributes as $attribute) {
            if ($attribute->getBackendType() === $type) {
                $attributeIds[] = $attribute->getId();
            }
        }
        
        return $attributeIds;
    }

    /**
     * Updates all option labels on tmp index table
     * 
     * @return $this
     */
    protected function _updateOptionLabel()
    {
        $deleteSelect = $this->_getIndexAdapter()->select();
        $deleteSelect
            ->from(array('tmp_idx' => $this->getIdxTable()))
            ->joinLeft(
                array('option' => $this->getTable('eav/attribute_option')),
                $this->_createCondition(
                    'option.option_id = tmp_idx.option_id',
                    'option.attribute_id = tmp_idx.attribute_id'
                ),
                array()
            )
            ->where('option.option_id IS NULL');

        $select = $this->_getIndexAdapter()->select();
        $select
            ->join(
                array('index' => $this->getIndexer()->getMainTable()),
                'index.document_id = tmp_index.document_id',
                array()
            )
            ->join(
                array('option' => $this->getTable('eav/attribute_option')),
                $this->_createCondition(
                    'option.attribute_id = tmp_index.attribute_id',
                    'option.option_id = tmp_index.option_id'
                ),
                array()
            )
            ->join(
                array('option_value' => $this->getTable('eav/attribute_option_value')),
                $this->_createCondition(
                    'option_value.option_id = option.option_id',
                    'option_value.store_id = 0'
                ),
                array()
            )
            ->joinLeft(
                array('option_value_store' => $this->getTable('eav/attribute_option_value')),
                $this->_createCondition(
                    'option_value_store.option_id = option.option_id',
                    'option_value_store.store_id = index.store_id'
                ),
                array()
            )
        ;
        
        $select->columns(array(
            'label' => $this->_getIndexAdapter()->getCheckSql(
                'option_value_store.value_id IS NOT NULL', 'option_value_store.value', 'option_value.value'
            )
        ));

        $this->_executeQueries(array(
            $this->_getIndexAdapter()->deleteFromSelect($deleteSelect, 'tmp_idx'),
            $this->_getIndexAdapter()->updateFromSelect($select, array('tmp_index' => $this->getIdxTable()))
        ));
        
        return $this;
    }

    /**
     * Updates configurable product options from child products
     *
     * @param Varien_Db_Select $limit
     * @return $this
     */
    protected function _insertConfigurableOptions($limit)
    {
        $select = $this->_getIndexAdapter()->select();
        $select->from(array('index' => $this->getIndexer()->getMainTable()), array());

        $select->where(
            'index.type_id = ?',
            Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE
        );

        if ($limit !== null) {
            $select->where('index.product_id IN(?)', $limit);
        }

        $select
            ->join(
                array('relation' => $this->getTable('catalog/product_super_link')),
                'index.product_id = relation.parent_id',
                array()
            )
            ->join(
                array('child_index' => $this->getIndexer()->getMainTable()),
                $this->_createCondition(
                    'child_index.product_id = relation.product_id',
                    'child_index.store_id = index.store_id'
                ),
                array()
            )
            ->join(
                array('attribute' => $this->getTable('catalog/product_super_attribute')),
                'attribute.product_id = index.product_id',
                array()
            )
            ->join(
                array('attribute_value' => $this->getTable(array('catalog/product', 'int'))),
                $this->_createCondition(
                    'attribute_value.entity_id = child_index.product_id',
                    'attribute_value.attribute_id = attribute.attribute_id',
                    $this->_quoteInto(
                        'attribute_value.store_id = ?',
                        Mage_Core_Model_App::ADMIN_STORE_ID
                    )
                ),
                array()
            )
            ->where(
                'child_index.status = ?',
                Mage_Catalog_Model_Product_Status::STATUS_ENABLED
            )
        ;


        if (!Mage::helper('cataloginventory')->isShowOutOfStock()) {
            $select->join(
                array('store' => $this->getTable('core/store')),
                'store.store_id = index.store_id',
                array()
            );

            $select->join(
                array('stock_status' => $this->getTable('cataloginventory/stock_status')),
                $this->_createCondition(
                    'stock_status.product_id = child_index.product_id',
                    'stock_status.website_id = store.website_id',
                    $this->_quoteInto('stock_status.stock_id = ?', 1)
                ),
                array()
            );

            $select->where(
                'stock_status.stock_status = ?',
                Mage_CatalogInventory_Model_Stock_Status::STATUS_IN_STOCK
            );
        }


        $selectColumns = array(
            'document_id' => 'index.document_id',
            'attribute_id' => 'attribute.attribute_id',
            'option_id' => 'attribute_value.value'
        );

        $select->columns($selectColumns);

        $this->_getIndexAdapter()->query(
            $this->_insertIgnoreIntoIndex($select, $selectColumns)
        );
        return $this;
    }
}
