<?php

interface EcomDev_Sphinx_Model_Resource_Index_Reader_Plugin_MemoryTableAwareInterface
{
    /**
     * Sets entity memory table name
     *
     * @param string|null $name
     * @return $this
     */
    public function setEntityTableName($name);
}
