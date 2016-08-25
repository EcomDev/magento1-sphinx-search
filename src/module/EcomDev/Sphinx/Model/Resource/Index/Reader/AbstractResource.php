<?php

abstract class EcomDev_Sphinx_Model_Resource_Index_Reader_AbstractResource
    extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * Created memory tables
     *
     * @var string[]
     */
    private $memoryTables = [];

    protected $memoryTableIsString = false;

    /**
     * Creates a memory table for faster export of identifiers
     *
     * @param $tableName
     * @throws Zend_Db_Exception
     * @return $this
     */
    private function createMemoryTable($tableName)
    {
        $name = uniqid('ecomdev_sphinx_' . $tableName);
        $tableDdl = new Varien_Db_Ddl_Table();
        $tableDdl->setName($name);

        if ($this->memoryTableIsString) {
            $tableDdl->addColumn('id', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, ['primary' => true]);
        } else {
            $tableDdl->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, ['primary' => true]);
        }

        $tableDdl->setOption('type', 'MEMORY');

        $this->_getReadAdapter()->createTable($tableDdl);
        $this->memoryTables[$tableName] = $name;
        return $this;
    }

    /**
     * Enables index switch
     *
     * @return $this
     */
    protected function enableIndexSwitch()
    {
        $this->getReadConnection()->query(
            'SET optimizer_switch=:switch',
            ['switch' => 'use_index_extensions=on']
        );
        return $this;
    }

    /**
     * Disables index switch back to default configuration server
     *
     * @return $this
     */
    protected function disableIndexSwitch()
    {
        $this->getReadConnection()->query(
            'SET optimizer_switch=:switch',
            ['switch' => 'use_index_extensions=default']
        );
        return $this;
    }

    /**
     * Creates a memory table for faster export of identifiers
     *
     * @param Varien_Db_Select $select
     * @param string[][] $indexes
     * @throws Zend_Db_Exception
     * @return $this
     */
    protected function createTemporaryTableFromSelect($select, array $indexes)
    {
        $name = uniqid('ecomdev_sphinx_' . crc32((string)$select));

        $indexStatements = [];
        foreach ($indexes as $indexName => $columns) {
            $renderedColumns = implode(',', array_map([$select->getAdapter(), 'quoteIdentifier'], $columns));

            $indexType = sprintf('INDEX %s', $select->getAdapter()->quoteIdentifier($indexName));

            if ($indexName === 'PRIMARY') {
                $indexType = 'UNIQUE';
            } elseif (strpos($indexName, 'UNQ_') === 0) {
                $indexType = sprintf('UNIQUE %s', $select->getAdapter()->quoteIdentifier($indexName));
            }

            $indexStatements[] = sprintf('%s(%s)', $indexType, $renderedColumns);
        }

        foreach ($indexes as $indexName => $columns) {
            if ($indexName === 'PRIMARY') {
                continue;
            }
        }

        $statement = sprintf(
            'CREATE TEMPORARY TABLE %s %s IGNORE (%s)',
            $select->getAdapter()->quoteIdentifier($name),
            $indexStatements ? '(' . implode(',', $indexStatements) . ')' : '',
            (string)$select
        );

        $select->getAdapter()->query(
            $statement,
            $select->getBind()
        );

        return $name;
    }

    /**
     * Drops a temporary table
     *
     * @param string $table
     * @return $this
     * @throws Zend_Db_Exception
     */
    protected function dropTemporaryTable($tableName)
    {
        $this->_getReadAdapter()->dropTemporaryTable($tableName);
        return $this;
    }



    /**
     * Memory table for identifiers
     *
     * @param string $tableName
     * @return string
     */
    protected function getMemoryTableName($tableName)
    {
        if (!isset($this->memoryTables[$tableName])) {
            $this->createMemoryTable($tableName);
        }

        return $this->memoryTables[$tableName];
    }

    /**
     * Fills memory table with data
     *
     * @param string $tableName
     * @param int[]|Varien_Db_Select $identifiers
     * @return $this
     */
    protected function fillMemoryTable($tableName, $identifiers)
    {
        $this->_getReadAdapter()->truncateTable($this->getMemoryTableName($tableName));

        if ($identifiers instanceof Varien_Db_Select) {
            $this->_getReadAdapter()->query(
                $identifiers->insertFromSelect($this->getMemoryTableName($tableName), ['id']),
                $identifiers->getBind()
            );
        } else {
            $this->_getReadAdapter()->insertArray(
                $this->getMemoryTableName($tableName),
                ['id'],
                $identifiers
            );
        }

        return $this;
    }

    protected function findIndexHint($table, $columns)
    {
        $indexes = $this->_getReadAdapter()->getIndexList($table);
        $expectedColumns = array_combine($columns, $columns);
        $byMatchCount = [];
        foreach ($indexes as $index) {
            $indexColumns = array_combine($index['COLUMNS_LIST'], $index['COLUMNS_LIST']);
            if ($matched = array_intersect_key($indexColumns, $expectedColumns)) {
                $byMatchCount[count($matched)] = $index['KEY_NAME'];
            }
        }

        if (isset($byMatchCount[count($columns)])) {
            return $byMatchCount[count($columns)];
        }

        return false;
    }

    /**
     * Removes created memory tables
     *
     */
    public function __destruct()
    {
        foreach ($this->memoryTables as $tableName) {
            $this->_getReadAdapter()->dropTable($tableName);
        }
    }
}
