<?php

use \Foolz\SphinxQL\Drivers\Pdo\Connection as PdoConnection;
use \Foolz\SphinxQL\Drivers\Mysqli\Connection as MysqliConnection;
use EcomDev_Sphinx_Model_Sphinx_Query_Builder as QueryBuilder;
use \Foolz\SphinxQL\Helper as SphinxHelper;

/**
 * Sphinx Container for shared data
 * 
 */
class EcomDev_Sphinx_Model_Sphinx_Container
{
    /**
     * Instance of sphinx connection
     * 
     * @var Connection
     */
    protected $_connection;

    /**
     * @var SphinxHelper
     */
    protected $_helper;

    /**
     * Contain list of index columns
     * 
     * @var string[][]
     */
    protected $_indexColumns = array();

    /**
     * Contain list of index fields
     *
     * @var string[][]
     */
    protected $_indexFields = array();

    /**
     * Contain list of index names for current store
     *
     * @var string[]
     */
    protected $_indexNames = array();

    /**
     * Contains a query builder class
     *
     * @var string
     */
    protected $_queryBuilderClass;

    protected $_searchMode = false;

    /**
     * Activates search mode for sphinx
     *
     * @return $this
     */
    public function activateSearchMode()
    {
        $this->_searchMode = true;
        return $this;
    }

    /**
     * Returns an instance of connection
     *
     * @return Connection
     */
    public function getConnection()
    {
        if ($this->_connection === null) {
            if (defined('HHVM_VERSION')) {
                $this->_connection = new MysqliConnection();
            } else {
                $this->_connection = new PdoConnection();
            }

            $this->_connection->setParam(
                'host', $this->getConfig()->getConfig('host', 'connection')
            );
            $this->_connection->setParam(
                'port', $this->getConfig()->getConfig('port', 'connection')
            );
            $this->_connection->connect(true);
        }

        return $this->_connection;
    }

    /**
     * Returns an instance of query builder
     *
     * @return QueryBuilder
     */
    public function queryBuilder()
    {
        if ($this->_queryBuilderClass === null) {
            $this->_queryBuilderClass = Mage::getConfig()
                ->getModelClassName('ecomdev_sphinx/sphinx_query_builder');
        }

        $class = $this->_queryBuilderClass;
        return new $class($this->getConnection());
    }

    /**
     * Instance of configuration model
     *
     * @return EcomDev_Sphinx_Model_Config
     */
    public function getConfig()
    {
        return Mage::getSingleton('ecomdev_sphinx/config');
    }

    /**
     * Returns a check if index is available
     *
     * @return bool
     */
    public function isAvailable()
    {
        $requiredTables = array(
            sprintf('product_%s', Mage::app()->getStore()->getId()),
            sprintf('product_search_%s', Mage::app()->getStore()->getId()),
            sprintf('category_%s', Mage::app()->getStore()->getId()),
        );

        $tables = array();

        try {
            $pingResult = $this->getConnection()->ping();
            if ($pingResult) {
                foreach ($this->getHelper()->showTables()->execute() as $row) {
                    $tables[] = $row['Index'];
                }
            }
        } catch (Exception $e) {
            Mage::logException($e);
            return false;
        }


        return count(array_diff($requiredTables, $tables)) === 0;
    }

    /**
     * Return list of index names based on type
     *
     * @param string $type
     * @return string[]
     */
    public function getIndexNames($type)
    {
        if ($type === 'product' && $this->_searchMode) {
            $type = 'product_search';
        }

        if (!isset($this->_indexNames[$type])) {
            $this->_indexNames[$type] = array(
                sprintf('%s_%s', $type, Mage::app()->getStore()->getId())
            );
        }

        return $this->_indexNames[$type];
    }

    /**
     * Returns a helper instance
     *
     * @return SphinxHelper
     */
    public function getHelper()
    {
        if ($this->_helper === null) {
            $this->_helper = SphinxHelper::create($this->getConnection());
        }

        return $this->_helper;
    }

    /**
     * Returns list of index columns
     *
     * @param string $indexName
     * @return string[]
     */
    public function getIndexColumns($indexName)
    {
        if (!isset($this->_indexColumns[$indexName])) {
            $this->_initIndexInfo($indexName);
        }

        return $this->_indexColumns[$indexName];
    }

    /**
     * Returns list of index fields
     *
     * @param string $indexName
     * @return string[]
     */
    public function getIndexFields($indexName)
    {
        if (!isset($this->_indexFields[$indexName])) {
            $this->_initIndexInfo($indexName);
        }

        return $this->_indexFields[$indexName];
    }

    /**
     * @param $indexName
     * @return $this
     */
    private function _initIndexInfo($indexName)
    {
        $realIndexName = current($this->getIndexNames($indexName));
        $this->_indexColumns[$indexName] = array();
        $this->_indexFields[$indexName] = array();
        foreach ($this->getHelper()->describe($realIndexName)->execute() as $info) {
            if ($info['Type'] === 'field') {
                $this->_indexFields[$indexName][$info['Field']] = $info['Field'];
                continue;
            }
            $this->_indexColumns[$indexName][$info['Field']] = $info['Field'];
        }

        return $this;
    }
}
