<?php

/**
 * Category snapshot model
 *
 */
class EcomDev_Sphinx_Model_Resource_Index_Reader_Snapshot_Category
    extends EcomDev_Sphinx_Model_Resource_Index_Reader_AbstractSnapshot
{
    protected function _construct()
    {
        $this->_init('catalog/category', 'entity_id');
    }

    protected function modifySelectFilters($select)
    {
        $select->join(
            ['entity_id' => $this->getEntityTableName()],
            'entity_id.id = main_table.entity_id',
            []
        );
        return $this;
    }
    
}
