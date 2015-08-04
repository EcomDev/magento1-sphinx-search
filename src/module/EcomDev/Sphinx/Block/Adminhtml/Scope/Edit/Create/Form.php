<?php

class EcomDev_Sphinx_Block_Adminhtml_Scope_Edit_Create_Form
    extends EcomDev_Sphinx_Block_Adminhtml_Edit_Form
{
    protected $_fieldNameSuffix = 'sphinx_scope';
    
    protected function _createFields()
    {
        $this->_addField('name', 'text', $this->__('Scope Name'));
       /* $this->_addField('parent_id', 'select', $this->__('Parent Scope'), array(
            'option_model' => 'ecomdev_sphinx/source_scope',
            'required' => false
        )); */
        
        $this->_fieldComment('name', $this->__('The value specified here is not visible to end client'));
        //$this->_fieldComment('parent_id', $this->__('Choose a scope to inherit from, if you need one'));
        return $this;
    }
}
