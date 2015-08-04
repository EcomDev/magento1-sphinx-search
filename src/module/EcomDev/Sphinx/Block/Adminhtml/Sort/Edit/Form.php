<?php

class EcomDev_Sphinx_Block_Adminhtml_Scope_Edit_Form
    extends EcomDev_Sphinx_Block_Adminhtml_Edit_Form
{
    protected $_fieldNameSuffix = 'sphinx_sort';

    protected function _createFields()
    {
        $this->_addField(
            'name', 'text',
            $this->__('Sort Name')
        );

        $this->_fieldComment(
            'name',
            $this->__('The value is show as a sort options on the frontend')
        );

        

        return $this;
    }
}
