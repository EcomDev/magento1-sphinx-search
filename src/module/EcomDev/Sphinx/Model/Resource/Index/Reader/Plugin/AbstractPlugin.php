<?php

abstract class EcomDev_Sphinx_Model_Resource_Index_Reader_Plugin_AbstractPlugin
    extends EcomDev_Sphinx_Model_Resource_Index_Reader_AbstractResource
    implements EcomDev_Sphinx_Contract_Reader_PluginInterface,
        EcomDev_Sphinx_Model_Resource_Index_Reader_Plugin_MemoryTableAwareInterface
{
    /**
     * Entity table name
     *
     * @var string|null
     */
    protected $entityMemoryTable;

    /**
     * Resource initialization
     *
     */
    protected function _construct()
    {
        $this->_setResource('ecomdev_sphinx');
    }

    /**
     * Sets entity table by filter
     *
     * @param null|string $name
     * @return $this
     */
    public function setEntityTableName($name)
    {
        $this->entityMemoryTable = $name;
        return $this;
    }

    /**
     * Returns main memory table
     *
     * @param $tableName
     * @return null|string
     */
    protected function getMainMemoryTable($tableName)
    {
        if ($this->entityMemoryTable) {
            return $this->entityMemoryTable;
        }

        return $this->getMemoryTableName($tableName);
    }
}
