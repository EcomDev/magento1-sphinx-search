<?php

class EcomDev_Sphinx_Model_Resource_Attribute_Collection
    extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    protected function _construct()
    {
        $this->_init('ecomdev_sphinx/attribute');
    }
    
    protected function _initSelect()
    {
        parent::_initSelect();
        $this->getSelect()->join(
            array(
                'attribute' => $this->getTable('eav/attribute')
            ),
            'main_table.attribute_id=attribute.attribute_id', 
            array('attribute_code', 
                  'attribute_name' => 'frontend_label',
                  'backend_type', 
                  'frontend_input',
                  'source_model')
        );
        $this->addFilterToMap('attribute_name', 'attribute.frontend_label');
        return $this;
    }
}
