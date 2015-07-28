<?php

use \Foolz\SphinxQL\Connection as Connection;
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
            $this->_connection = new Connection();
            $this->_connection->setParam(
                'host', $this->getConfig()->getConfig('host', 'connection')
            );
            $this->_connection->setParam(
                'port', $this->getConfig()->getConfig('port', 'connection')
            );
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
            $realIndexName = current($this->getIndexNames($indexName));
            $this->_indexColumns[$indexName] = array();
            foreach ($this->getHelper()->describe($realIndexName)->execute() as $info) {
                if ($info['Type'] === 'field') {
                    continue;
                }
                $this->_indexColumns[$indexName][] = $info['Field'];
            }
        }
        
        return $this->_indexColumns[$indexName];
    }
}
