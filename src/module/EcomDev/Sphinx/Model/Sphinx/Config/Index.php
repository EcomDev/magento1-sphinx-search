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
     * Indexer code for keyword
     *
     */
    const INDEX_KEYWORD = 'keyword';

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

        $indexPath = rtrim($this->_getConfig()->getIndexPath(), '/');

        $baseType = 'csv';
        $deltaType = 'xml';

        $config['sources']['category_base'] = $this->getBaseIndexSource('category', $baseType);
        $config['sources']['category_delta_base'] = $this->getBaseIndexSource('category', $deltaType);

        $config['sources']['product_base'] = $this->getBaseIndexSource('product', $baseType);
        $config['sources']['product_delta_base'] = $this->getBaseIndexSource('product', $deltaType);

        $config['sources']['keyword_base'] = $this->getBaseIndexSource('keyword', $baseType);

        $stores = Mage::app()->getStores(false);

        foreach ($stores as $store) {
            $storeId = $store->getId();

            $config['sources'][sprintf('category_%s : category_base', $storeId)] = $this->getCommandSource(
                'category', $baseType, $storeId
            );

            $config['sources'][sprintf('category_delta_%s : category_delta_base', $storeId)] = $this->getCommandSource(
                'category', $deltaType, $storeId, [], true
            );

            $config['sources'][sprintf('product_%s : product_base', $storeId)] = $this->getCommandSource(
                'product', $baseType, $storeId, ['--visibility', 'catalog']
            );

            $config['sources'][sprintf('product_delta_%s : product_delta_base', $storeId)] = $this->getCommandSource(
                'product', $deltaType, $storeId, ['--visibility', 'catalog'], true
            );

            $config['sources'][sprintf('product_search_%s : product_base', $storeId)] = $this->getCommandSource(
                'product', $baseType, $storeId, ['--visibility', 'search']
            );

            $config['sources'][sprintf('product_search_delta_%s : product_delta_base', $storeId)] = $this->getCommandSource(
                'product', $deltaType, $storeId, ['--visibility', 'search'], true
            );

            $config['sources'][sprintf('keyword_%s : keyword_base', $storeId)] = $this->getCommandSource(
                'keyword', $baseType, $storeId
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

            $config['indexes'][sprintf('keyword_%s', $storeId)] = array(
                sprintf('source = keyword_%s', $storeId),
                sprintf('path = %s/%s_%s', $indexPath, 'keyword', $storeId)
            );
        }

        return $config;
    }

    /**
     * Service instance
     *
     * @return EcomDev_Sphinx_Model_Index_Service
     */
    private function getService()
    {
        return Mage::getSingleton('ecomdev_sphinx/index_service');
    }

    /**
     * @param string $format
     * @param bool $includeType
     * @return string
     */
    private function getPrefixToFormat($format, $includeType = false)
    {
        if ($format === 'xml') {
            $prefix = 'xmlpipe';
            $type = 'xmlpipe2';
        } elseif ($format === 'tsv') {
            $prefix = 'tsvpipe';
            $type = $prefix;
        } elseif ($format === 'csv') {
            $prefix = 'csvpipe';
            $type = $prefix;
        } else {
            throw new InvalidArgumentException('Unknown format specified: ' . $format);
        }

        if ($includeType) {
            return [$prefix, $type];
        }

        return $prefix;
    }

    /**
     * Source definition
     *
     * @param $type
     * @param $format
     * @return array
     */
    private function getBaseIndexSource($type, $format)
    {
        $configuration = $this->getService()->getConfiguration($type);

        list($prefix, $sourceType) = $this->getPrefixToFormat($format, true);

        $source = [
            sprintf('type = %s', $sourceType)
        ];

        /** @var EcomDev_Sphinx_Contract_FieldInterface|EcomDev_Sphinx_Contract_Field_LengthAwareInterface $field */
        foreach ($configuration->getFields() as $field) {
            $fieldName = $field->getName();
            if ($field instanceof EcomDev_Sphinx_Contract_Field_LengthAwareInterface) {
                $fieldName .= ':' . $field->getLength();
            }

            $source[] = sprintf('%s_%s = %s', $prefix, $field->getType(), $fieldName);
        }

        return $source;
    }

    /**
     * Type of index
     *
     * @param string $type
     * @param string $format
     * @param int $storeId
     * @param bool $isDelta
     * @param string[] $additionalArguments
     * @return string[]
     */
    private function getCommandSource($type, $format, $storeId, array $additionalArguments = [], $isDelta = false)
    {
        $reflectionClass = new ReflectionClass('Mage');

        $filePath = dirname(dirname($reflectionClass->getFileName())) . DS . 'shell' . DS . 'ecomdev-sphinx.php';

        $deltaFlag = $isDelta ? '--delta' : '';

        $prefix = $this->getPrefixToFormat($format);

        $command = sprintf(
            'php -f %s -- export:%s --store %d --format %s %s %s',
            $filePath, $type, $storeId, $format, $deltaFlag, implode(' ', $additionalArguments)
        );

        $source = [];
        $source[] = sprintf('%s_command = %s', $prefix, $command);

        return $source;
    }

    /**
     * Return number of index rows that should be updated
     * 
     * @param string $index
     * @return int
     */
    public function getPendingRowCount($index, $storeId)
    {
        return (int)$this->getResource()->getPendingRowCount($index, $storeId);
    }
}
