<?php

class EcomDev_Sphinx_Block_Adminhtml_Attribute_Edit_Form
    extends EcomDev_Sphinx_Block_Adminhtml_Edit_Form
{

    protected $_fieldNameSuffix = 'sphinx_attribute';
    
    protected function _createFields()
    {
        $dataObject = $this->getDataObject();
        $attribute = $dataObject->getAttribute();
        $this->_addField('attribute', 'note', $this->__('Attribute'), array(
            'text' => $this->__(
                '%s (code: %s)',
                $attribute->getFrontendLabel(),
                $attribute->getAttributeCode()
            ),
            'required' => false
        ));
        
        $this->_addField('is_active', 'select', $this->__('Is Active'), array(
            'option_model' => 'ecomdev_sphinx/source_yesno',
            'disabled' => $attribute->getIsSystem() > 0
        ));
        
        if (in_array($attribute->getBackendType(), array('int', 'decimal')) 
            || $dataObject->isOption()) {
            $this->_addField('is_layered', 'select', $this->__('Use In Layered Navigation'), array(
                'option_model' => 'ecomdev_sphinx/source_yesno'
            ));

            $excludedOptions = array();
            
            if ($dataObject->isOption()) {
                $excludedOptions[] = EcomDev_Sphinx_Model_Source_Attribute_Filter_Type::TYPE_RANGE;
                $excludedOptions[] = EcomDev_Sphinx_Model_Source_Attribute_Filter_Type::TYPE_SLIDER;
            } else {
                $excludedOptions[] = EcomDev_Sphinx_Model_Source_Attribute_Filter_Type::TYPE_SINGLE;
                $excludedOptions[] = EcomDev_Sphinx_Model_Source_Attribute_Filter_Type::TYPE_MULTIPLE;
            }
            
            $this->_addField('filter_type', 'select', $this->__('Type of Filter'), array(
                'option_model' => 'ecomdev_sphinx/source_attribute_filter_type',
                'excluded_options' => $excludedOptions
            ));
    
            $this->_addField('is_custom_value_allowed', 'select', $this->__('Allow Custom Value Input for Filter'), array(
                'option_model' => 'ecomdev_sphinx/source_yesno',
                'required' => false
            ));       
            
            $this->_fieldDependence(
                'is_custom_value_allowed', 
                'filter_type', 
                EcomDev_Sphinx_Model_Source_Attribute_Filter_Type::TYPE_RANGE
            );

            $this->_fieldDependence(
                'filter_type',
                'is_layered',
                '1'
            );
        }


        if (!in_array($attribute->getBackendType(), array('decimal', 'datetime'))) {
            $this->_addField('is_fulltext', 'select', $this->__('Use In Fulltext Search'), array(
                'option_model' => 'ecomdev_sphinx/source_yesno'
            ));
        }

        $this->_addField('is_sort', 'select', $this->__('Use In Sorting Results'), array(
            'option_model' => 'ecomdev_sphinx/source_yesno'
        ));
        
        return $this;
    }
}
