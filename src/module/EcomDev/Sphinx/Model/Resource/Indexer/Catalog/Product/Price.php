<?php

/**
 * Indexer for a product option sub indexer
 * 
 * 
 */
class EcomDev_Sphinx_Model_Resource_Indexer_Catalog_Product_Price
    extends EcomDev_Sphinx_Model_Resource_Indexer_Catalog_Product_AbstractIndexer
{

    /**
     * Resource initialization
     */
    protected function _construct()
    {
        $this->_init('ecomdev_sphinx/index_product_price', 'document_id');
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
        $select = $this->_getIndexAdapter()->select();
        $columns = array(
            'document_id' => 'index.document_id',
            'customer_group_id' => 'price_index.customer_group_id',
            'price' => 'price_index.price',
            'final_price' => 'price_index.final_price',
            'min_price' => 'price_index.min_price',
            'max_price' => 'price_index.max_price'
        );
        
        $select
            ->from(array('index' => $this->getIndexer()->getMainTable()), array())
            ->join(
                array('store' => $this->getTable('core/store')), 
                'index.store_id = store.store_id', 
                array()
            )
            ->join(
                array('price_index' => $this->getTable('catalog/product_index_price')),
                $this->_createCondition(
                    'price_index.entity_id = index.product_id',
                    'price_index.website_id = store.website_id'
                ),
                array()
            )
            ->columns($columns);
        
        
        $this->_getIndexAdapter()->query(
            $this->_insertIgnoreIntoIndex($select, $columns)
        );
        
        return $this;
    }
}
