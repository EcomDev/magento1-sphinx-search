<?php

class EcomDev_Sphinx_Model_Source_Product_Attribute
    extends EcomDev_Sphinx_Model_Source_AbstractOption
{

    protected function _initOptions()
    {
        $this->_options = array();
        $eavConfig = Mage::getSingleton('eav/config');
        $entityTypeCode = Mage_Catalog_Model_Product::ENTITY;
        $attributeCodes = $eavConfig->getEntityAttributeCodes($entityTypeCode);
        
        foreach ($attributeCodes as $attributeCode) {
            $attribute = $eavConfig->getAttribute($entityTypeCode, $attributeCode);
            if ($attribute->getData('backend_table')
                || (!$attribute->getIsUserDefined() 
                        && !in_array($attribute->getFrontendInput(), array('media_image', 'select'))) 
                || $attribute->getBackendType() === 'static'
                || trim($attribute->getFrontendLabel()) === '') {
                continue;
            }
            
            $this->_options[$attribute->getAttributeCode()] = $this->__('%s (code: %s)', $attribute->getFrontendLabel(), $attributeCode);
        }
        
        return $this;
    }
}
