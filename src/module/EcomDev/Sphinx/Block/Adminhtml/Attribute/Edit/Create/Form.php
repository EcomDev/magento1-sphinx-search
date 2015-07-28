<?php

class EcomDev_Sphinx_Block_Adminhtml_Attribute_Edit_Create_Form
    extends EcomDev_Sphinx_Block_Adminhtml_Edit_Form
{

    protected function _createFields()
    {
        $this->_addField('attribute_id', 'select', $this->__('Choose Attribute'), array(
            'option_model' => 'ecomdev_sphinx/source_product_attribute',
            'excluded_options' => Mage::getResourceSingleton('ecomdev_sphinx/attribute')->getUsedAttributeCodes()
        ));
        return $this;
    }
}
