<?php

use EcomDev_Sphinx_Model_Resource_Indexer_Catalog_Product as ProductIndexer;
use EcomDev_Sphinx_Model_Config as ConfigInstance;

/**
 * Indexer interface 
 * 
 */
interface EcomDev_Sphinx_Model_Resource_Indexer_Catalog_Product_IndexerInterface
{
    /**
     * Re-indexes data for products
     * Returns number of changed rows 
     * 
     * @param Varien_Db_Select|null $limit
     * @return int
     */
    public function reindexData(Varien_Db_Select $limit = null);

    /**
     * Set an indexer instance
     *
     * @param ProductIndexer $indexer
     * @return $this
     */
    public function setIndexer(ProductIndexer $indexer);

    /**
     * Return an indexer instance
     * 
     * @return ProductIndexer
     */
    public function getIndexer();

    /**
     * Sets configuration instance for an indexer
     * 
     * @param ConfigInstance $config
     * @return $this
     */
    public function setConfig(ConfigInstance $config);

    /**
     * Returns an instance of configuration
     * 
     * @return ConfigInstance
     */
    public function getConfig();
}
