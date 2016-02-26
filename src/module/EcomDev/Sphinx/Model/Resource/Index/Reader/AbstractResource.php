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
     * @param int[] $identifiers
     * @return $this
     */
    protected function fillMemoryTable($tableName, $identifiers)
    {
        $this->_getReadAdapter()->truncateTable($this->getMemoryTableName($tableName));
        $this->_getReadAdapter()->insertArray(
            $this->getMemoryTableName($tableName),
            ['id'],
            $identifiers
        );

        return $this;
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
