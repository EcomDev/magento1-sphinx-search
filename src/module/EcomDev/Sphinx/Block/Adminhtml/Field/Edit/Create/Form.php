<?php

/**
 *
 * @method EcomDev_Sphinx_Model_Field getDataObject()
 */
class EcomDev_Sphinx_Block_Adminhtml_Field_Edit_Create_Form
    extends EcomDev_Sphinx_Block_Adminhtml_Edit_Form
{
    protected $_fieldNameSuffix = 'sphinx_field';


    protected function _createFields()
    {
        $this->_addField('code', 'text', $this->__('Code'), [
            'note' => $this->__('Unique code for a virtual field'),
            'class' => 'validate-code'
        ]);

        $this->_addField('name', 'text', $this->__('Field Name'));
        $this->_addField('type', 'select', $this->__('Field Type'), [
            'option_model' => 'ecomdev_sphinx/source_field_type'
        ]);

        $this->_addField('related_attribute', 'select', $this->__('Attribute'), [
            'option_model' => 'ecomdev_sphinx/source_attribute_active'
        ]);

        $this->_fieldDependence('related_attribute', 'type', [
            EcomDev_Sphinx_Model_Source_Field_Type::TYPE_GROUPED,
            EcomDev_Sphinx_Model_Source_Field_Type::TYPE_RANGE,
            EcomDev_Sphinx_Model_Source_Field_Type::TYPE_ALIAS
        ]);

        return $this;
    }
}
