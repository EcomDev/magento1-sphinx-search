<?php

class EcomDev_Sphinx_Model_Resource_Scope_Collection
    extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    protected function _construct()
    {
        $this->_init('ecomdev_sphinx/scope');
    }
    
    public function joinParentName()
    {
        if (!$this->getFlag('parent_name_joined')) {
            $this->getSelect()->joinLeft(
                array('parent' => $this->getMainTable()),
                'parent.scope_id = main_table.parent_id',
                array()
            );
            
            $this->getSelect()->columns(array('parent_name' => 'parent.name'));
            
            $this->setFlag('parent_name_joined', true);
            $this->addFilterToMap('name', 'main_table.name');
            $this->addFilterToMap('parent_id', 'main_table.parent_id');
            $this->addFilterToMap('parent_name', 'parent.name');
            $this->addFilterToMap('scope_id', 'main_table.scope_id');
        }
        
        return $this;
    }
}
