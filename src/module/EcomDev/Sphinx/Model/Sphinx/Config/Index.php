<?php

class EcomDev_Sphinx_Model_Sphinx_Config_Index
    extends EcomDev_Sphinx_Model_Sphinx_AbstractConfig
    implements EcomDev_Sphinx_Model_Sphinx_ConfigInterface
{
    /**
     * Indexer code for product
     */
    const INDEX_PRODUCT = 'product';

    /**
     * Indexer code for category
     */
    const INDEX_CATEGORY = 'category';

    /**
     * Indexer code for product delta
     */
    const INDEX_PRODUCT_DELTA = 'product_delta';

    /**
     * Indexer code for category delta
     */
    const INDEX_CATEGORY_DELTA = 'category_delta';

    /**
     * Constructs resource model
     * 
     */
    public function __construct()
    {
        $this->_resourceModel = 'ecomdev_sphinx/sphinx_config_index';
    }
    
    /**
     * Renders configuration of indexes for sphinx
     *
     * @return string
     */
    public function render()
    {        
        $statements = $this->_getStatements();

        $renderedFile = array();

        foreach ($statements['sources'] as $sourceName => $configItem) {
            $renderedFile[] = sprintf(
                "source %s \n{ \n%s\n}\n",
                $sourceName,
                $this->_renderStrings($configItem)
            );
        }

        foreach ($statements['indexes'] as $indexName => $configItem) {
            $renderedFile[] = sprintf(
                "index %s \n{ \n%s\n}\n",
                $indexName,
                $this->_renderStrings($configItem)
            );
        }
        
        return implode("\n", $renderedFile);
    }

    protected function _getStatements()
    {
        $config = array(
            'sources' => array(),
            'indexes' => array()
        );

        $connection = $this->getDbConnection();
        $config['sources']['base'] = array(
            'type = mysql'
        );

        foreach ($connection as $key => $value) {
            $config['sources']['base'][] = sprintf('sql_%s = %s', $key, $value);
        }

        $indexPath = rtrim($this->_getConfig()->getIndexPath(), '/');

        $config['sources']['category_base : base'] = $this->_getBaseCategorySource();
        $config['sources']['product_base : base'] = $this->_getBaseProductSource();

        $stores = Mage::app()->getStores(false);
        $lastStore = end($stores);

        foreach ($stores as $store) {
            $storeId = $store->getId();
            $isLastStore = $storeId == $lastStore->getId();

            $config['sources'][sprintf('category_%s : category_base', $storeId)] = $this->_getCategorySource(
                $storeId, false, $isLastStore
            );

            $config['sources'][sprintf('category_delta_%s : category_base', $storeId)] = $this->_getCategorySource(
                $storeId, true, $isLastStore
            );

            $config['sources'][sprintf('product_%s : product_base', $storeId)] = $this->_getProductSource(
                $storeId, false, false, Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_CATALOG
            );

            $config['sources'][sprintf('product_search_%s : product_base', $storeId)] = $this->_getProductSource(
                $storeId, false, $isLastStore, Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_SEARCH
            );

            $config['sources'][sprintf('product_delta_%s : product_base', $storeId)] = $this->_getProductSource(
                $storeId, true, false, Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_CATALOG
            );

            $config['sources'][sprintf('product_search_delta_%s : product_base', $storeId)] = $this->_getProductSource(
                $storeId, true, $isLastStore, Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_SEARCH
            );

            $config['indexes'][sprintf('category_%s', $storeId)] = array(
                sprintf('source = category_%s', $storeId),
                sprintf('path = %s/%s_%s', $indexPath, 'category', $storeId)
            );

            $config['indexes'][sprintf('category_delta_%s', $storeId)] = array(
                sprintf('source = category_delta_%s', $storeId),
                sprintf('path = %s/%s_%s', $indexPath, 'category_delta', $storeId)
            );

            $config['indexes'][sprintf('product_%s', $storeId)] = array(
                sprintf('source = product_%s', $storeId),
                sprintf('path = %s/%s_%s', $indexPath, 'product', $storeId)
            );

            $config['indexes'][sprintf('product_search_%s', $storeId)] = array(
                sprintf('source = product_search_%s', $storeId),
                sprintf('path = %s/%s_%s', $indexPath, 'product_search', $storeId)
            );

            $config['indexes'][sprintf('product_delta_%s', $storeId)] = array(
                sprintf('source = product_delta_%s', $storeId),
                sprintf('path = %s/%s_%s', $indexPath, 'product_delta', $storeId)
            );

            $config['indexes'][sprintf('product_search_delta_%s', $storeId)] = array(
                sprintf('source = product_search_delta_%s', $storeId),
                sprintf('path = %s/%s_%s', $indexPath, 'product_search_delta', $storeId)
            );
        }

        return $config;
    }

    public function getDbConnection()
    {
        return $this->getResource()->getDbConnection();
    }

    protected function _getCategorySource($storeId, $isDelta = false, $isLast = false)
    {
        $source = array();
        foreach ($this->getResource()->getPreIndexStatements($isDelta, self::INDEX_CATEGORY, $storeId) as $statement) {
            $source[] = sprintf('sql_query_pre = %s', (string)$statement);
        }

        if ($isDelta) {
            $source[] = sprintf('sql_query_killlist = %s', $this->getResource()->getKillListSelect(self::INDEX_CATEGORY));
        }

        if (!$isDelta || $isLast) {
            foreach ($this->getResource()->getPostIndexStatements(self::INDEX_CATEGORY) as $statement) {
                $source[] = sprintf('sql_query_post_index = %s', (string)$statement);
            }
        }

        return $source;
    }

    /**
     * @param bool $isDelta
     * @return array
     */
    protected function _getProductSource($storeId, $isDelta = false, $isLast = false, $visibility)
    {
        $source = array();

        foreach ($this->getResource()->getPreIndexStatements($isDelta, self::INDEX_PRODUCT, $storeId, $visibility) as $statement) {
            $source[] = sprintf('sql_query_pre = %s', (string)$statement);
        }

        if ($isDelta) {
            $source[] = sprintf('sql_query_killlist = %s', $this->getResource()->getKillListSelect(self::INDEX_PRODUCT));
        }

        if ($isLast) {
            foreach ($this->getResource()->getPostIndexStatements(self::INDEX_PRODUCT) as $statement) {
                $source[] = sprintf('sql_query_post_index = %s', (string)$statement);
            }
        }

        return $source;
    }


    protected function _getBaseCategorySource()
    {
        $categoryAttributes = array(
            'category_id' => 'attr_uint',
            'store_id' => 'attr_uint',
            'name' => 'field_string',
            'description' => 'field_string',
            'image' => 'attr_string',
            'thumbnail' => 'attr_string',
            'path' => 'field_string',
            'is_active' => 'attr_bool',
            'include_in_menu' => 'attr_bool',
            'position' => 'attr_uint',
            'level' => 'attr_uint',
            'request_path' => 'field_string'
        );

        $source[] = sprintf('sql_query_range = %s', (string)$this->getResource()->getIndexRangeSelect(self::INDEX_CATEGORY));
        $source[] = sprintf('sql_range_step = %s', 50000);
        $source[] = sprintf('sql_query = %s', (string)$this->getResource()->getCategoryIndexDataSelect(true));
        foreach ($categoryAttributes as $attributeCode => $type) {
            $source[] = sprintf('sql_%s = %s', $type, $attributeCode);
        }

        return $source;
    }

    protected function _getBaseProductSource()
    {
        $source = array();

        $source[] = sprintf('sql_query_range = %s', (string)$this->getResource()->getIndexRangeSelect(self::INDEX_PRODUCT));
        $source[] = sprintf('sql_range_step = %s', 50000);
        $source[] = sprintf('sql_query = %s', (string)$this->getResource()->getProductIndexDataSelect(true));
        $plainAttributes = $this->_getConfig()->getPlainAttributes();
        $indexAttributes = $this->_getConfig()->getIndexAttributes();
        $systemAttributes = array(
            'name', 'sku', 'description', 'short_description',  'request_path',
            'product_id' => 'attr_uint',
            'store_id' => 'attr_uint',
            'attribute_set_id' => 'attr_uint',
            'has_options' => 'attr_bool',
            'required_options' => 'attr_bool',
            'type_id' => 'attr_string',
            'tax_class_id:4' => 'attr_uint',
            'visibility:4' => 'attr_uint',
            'status:4' => 'attr_uint',
            'stock_status:4' => 'attr_uint'
        );

        foreach ($systemAttributes as $attributeCode => $attributeType) {
            if (is_int($attributeCode)) {
                $attributeCode = $attributeType;
                $attributeType = 'field_string';
            }

            $source[] = sprintf('sql_%s = %s', $attributeType, $attributeCode);
        }

        foreach ($plainAttributes as $attributeCode => $attribute) {
            if (!in_array($attributeCode, $indexAttributes)) {
                continue;
            }
            $source[] = sprintf('sql_%s = %s', $attribute->getSphinxType(), $attributeCode);
        }

        foreach ($this->_getConfig()->getIndexPriceAttributes() as $priceField) {
            $source[] = sprintf('sql_attr_float = %s', $priceField);
        }

        foreach ($this->_getConfig()->getAttributesByType('option') as $attributeCode => $optionAttribute) {
            if (!$optionAttribute->getIsActive()) {
                continue;
            }

            $attributeExpression = $this->getResource()->getOptionAttributeRangedQuery(
                $optionAttribute->getId()
            );

            $type = 'ranged-query';
            if (!is_array($attributeExpression)) {
                $type = 'query';
                $attributeExpression = array($attributeExpression);
            }

            array_unshift($attributeExpression, sprintf('uint %s from %s', $attributeCode, $type));
            $source[] = sprintf('sql_attr_multi = %s', implode("; \n", $attributeExpression));

            if ($optionAttribute->getIsFulltext()) {
                $fieldExpression = $this->getResource()->getOptionFieldRangedQuery(
                    $optionAttribute->getId()
                );
                $type = 'ranged-query';
                if (!is_array($fieldExpression)) {
                    $type = 'query';
                    $fieldExpression = array($fieldExpression);
                }

                array_unshift($fieldExpression, sprintf('s_%s_label from %s', $attributeCode, $type));
                $source[] = sprintf('sql_joined_field = %s', implode("; \n", $fieldExpression));
            }
        }

        $categoryExpression = $this->getResource()->getCategoryAttributeQuery();
        array_unshift($categoryExpression, sprintf('uint %s from ranged-query', 'direct_category_ids'));
        $source[] = sprintf('sql_attr_multi = %s', implode("; \n", $categoryExpression));

        $categoryExpression = $this->getResource()->getCategoryAttributeQuery(false);
        array_unshift($categoryExpression, sprintf('uint %s from ranged-query', 'anchor_category_ids'));
        $source[] = sprintf('sql_attr_multi = %s', implode("; \n", $categoryExpression));

        $categoryExpression = $this->getResource()->getCategoryAttributeQuery(true, true, true);
        array_unshift($categoryExpression, sprintf('s_%s from ranged-query', 'direct_category_ids'));
        $source[] = sprintf('sql_joined_field = %s', implode("; \n", $categoryExpression));

        $categoryExpression = $this->getResource()->getCategoryAttributeQuery(false, true, true);
        array_unshift($categoryExpression, sprintf('s_%s from ranged-query', 'anchor_category_ids'));
        $source[] = sprintf('sql_joined_field = %s', implode("; \n", $categoryExpression));

        return $source;
    }

    /**
     * Return number of index rows that should be updated
     * 
     * @param string $index
     * @return int
     */
    public function getPendingRowCount($index)
    {
        return (int)$this->getResource()->getPendingRowCount($index);
    }
}
