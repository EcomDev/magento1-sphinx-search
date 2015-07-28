<?php

abstract class EcomDev_Sphinx_Model_Resource_Indexer_Catalog_Product_AbstractAttributeIndexer
    extends EcomDev_Sphinx_Model_Resource_Indexer_Catalog_Product_AbstractIndexer
{
    abstract protected function _getType();

    /**
     * Re-indexes data for products
     * Returns number of changed rows
     *
     * @param Varien_Db_Select $limit
     * @return $this
     */
    protected function _reindexData(Varien_Db_Select $limit = null)
    {
        $attributes = $this->_getConfig()->getAttributesByType($this->_getType());
        $attributeValueTable = $this->getValueTable('catalog/product', $this->_getType());
        $attributeIds = $this->_getAttributeIds($attributes);

        $this->_insertAttributeValues(
            $attributeValueTable,
            $attributeIds,
            function (Varien_Db_Select $select) {
                $selectColumns = array(
                    'document_id' => 'index.document_id',
                    'attribute_id' => 'attribute.attribute_id',
                    'value' => 'attribute.value'
                );

                $select->columns($selectColumns);
                return $this->_insertIgnoreIntoIndex($select, $selectColumns);
            },
            $limit
        );
        
        // Remove empty records
        $this->_getIndexAdapter()->delete(
            $this->getIdxTable(),
            'value IS NULL'
        );
        
        return $this;
    }
}
