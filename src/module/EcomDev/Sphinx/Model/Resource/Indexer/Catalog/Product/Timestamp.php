<?php

/**
 * Indexer for a product option sub indexer
 * 
 * 
 */
class EcomDev_Sphinx_Model_Resource_Indexer_Catalog_Product_Timestamp
    extends EcomDev_Sphinx_Model_Resource_Indexer_Catalog_Product_AbstractAttributeIndexer
{

    /**
     * Resource initialization
     */
    protected function _construct()
    {
        $this->_init('ecomdev_sphinx/index_product_timestamp', 'document_id');
    }

    protected function _getType()
    {
        return 'datetime';
    }
}
