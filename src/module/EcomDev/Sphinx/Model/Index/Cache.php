<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

use Mage_Catalog_Model_Product_Visibility as Visibility;

class EcomDev_Sphinx_Model_Index_Cache
{
    /** @var EcomDev_Sphinx_Model_Config */
    private $config;

    /** @var EcomDev_Sphinx_Model_Index_Service */
    private $indexService;

    /** @var EcomDev_Sphinx_Model_Resource_Sphinx_Config_Index_Collection */
    private $indexCollection;

    /**
     * Delimiter character of csv
     *
     * @var string
     */
    private $delimiter = ',';

    /**
     * Enclosure character of csv
     *
     * @var string
     */
    private $enclosure = '"';

    /**
     * Escape character of csv
     *
     * @var string
     */
    private $escape = '\\';

    public function __construct()
    {
        $this->config = Mage::getSingleton('ecomdev_sphinx/config');
        $this->indexService = Mage::getSingleton('ecomdev_sphinx/index_service');
        $this->indexCollection = Mage::getResourceSingleton('ecomdev_sphinx/sphinx_config_index_collection');
    }

    public function createProductCache()
    {
        $visibility = [
            Visibility::VISIBILITY_BOTH,
            Visibility::VISIBILITY_IN_SEARCH,
            Visibility::VISIBILITY_IN_CATALOG
        ];

        $this->createCacheDirectory();

        foreach ($this->indexCollection->getStoreIds() as $storeId) {
            $output = $this->getProductCacheFileName($storeId);
            $reader = $this->indexService->getReader('product');
            $scope = $this->indexService->getProductScope($storeId, $visibility);
            $writer = $this->indexService->getWriter('csv', $output);
            $writer->setOutputHeaders(true);
            $writer->process($reader, $scope);
        }
    }

    public function fetchProducts(int $storeId, array $visibility)
    {
        $cachedFile = $this->getProductCacheFileName($storeId);

        if (!file_exists($cachedFile)) {
            return;
        }

        $reader = $this->createReader($cachedFile);
        $headerMap = array_flip($reader->fetchOne());

        $reader->setOffset(1);

        foreach ($reader->fetch() as $row) {
            if (!in_array($row[$headerMap['visibility']], $visibility)) {
                continue;
            }

            $data = [];

            foreach ($headerMap as $columnName => $index) {
                $data[$columnName] = $row[$index] ?? null;
            }

            yield $data;
        }

    }

    public function exportProducts(
        int $storeId,
        array $visibility,
        string $output,
        bool $includeHeaders = false
    ) {
        $writer = $this->createWriter($output);

        foreach ($this->fetchProducts($storeId, $visibility) as $row) {
            if ($includeHeaders) {
                $writer->insertOne(array_keys($row));
                $includeHeaders = false;
            }

            $writer->insertOne($row);
        }
    }

    public function exportProductsWithColumns(
        int $storeId,
        array $visibility,
        string $output,
        array $columns,
        bool $includeHeaders = false
    ) {
        $writer = $this->createWriter($output);

        if ($includeHeaders) {
            $writer->insertOne($columns);
        }

        foreach ($this->fetchProducts($storeId, $visibility) as $row) {
            $data = [];
            foreach ($columns as $column) {
                $data[] = $row[$column] ?? null;
            }

            $writer->insertOne($data);
        }
    }


    /**
     *
     * @return string
     */
    private function getCacheDir()
    {
        return $this->config->getIndexPath() . DS . 'cache';
    }

    /**
     * @param $storeId
     *
     * @return string
     */
    private function getProductCacheFileName($storeId)
    {
        return sprintf('%s/product_%s.csv', $this->getCacheDir(), $storeId);
    }

    private function createCacheDirectory()
    {
        if (!is_dir($this->getCacheDir())) {
            mkdir($this->getCacheDir(), 0755, true);
        }
    }

    private function createWriter(string $path): \League\Csv\Writer
    {
        \Ajgl\Csv\Rfc\CsvRfcWriteStreamFilter::register();
        $csvWriter = League\Csv\Writer::createFromPath($path, 'w');
        $csvWriter->appendStreamFilter(\Ajgl\Csv\Rfc\CsvRfcWriteStreamFilter::FILTERNAME_DEFAULT);
        $csvWriter->setDelimiter($this->delimiter);
        $csvWriter->setEscape($this->escape);
        $csvWriter->setEnclosure($this->enclosure);

        return $csvWriter;
    }

    private function createReader(string $path): \League\Csv\Reader
    {
        \Ajgl\Csv\Rfc\CsvRfcWriteStreamFilter::register();
        $csvReader = League\Csv\Reader::createFromPath($path, 'r');
        $csvReader->appendStreamFilter(\Ajgl\Csv\Rfc\CsvRfcWriteStreamFilter::FILTERNAME_DEFAULT);
        $csvReader->setDelimiter($this->delimiter);
        $csvReader->setEscape($this->escape);
        $csvReader->setEnclosure($this->enclosure);
        return $csvReader;
    }

    private function fetchReader(array $visibility, $reader)
    {
        $reader->setOffset(1);
        foreach ($reader->fetch() as $row) {
            if (!in_array($row['visibility'], $visibility)) {
                return;
            }
            yield $row;
        }
    }
}
