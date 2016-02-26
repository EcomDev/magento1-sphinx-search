<?php

class EcomDev_Sphinx_Model_Source_Attribute_Active
    extends EcomDev_Sphinx_Model_Source_AbstractOption
{

    protected function _initOptions()
    {
        $attributes = Mage::getSingleton('ecomdev_sphinx/config')->getActiveAttributes();

        $proxy = new stdClass();
        $proxy->additionalAttributes = [];

        Mage::dispatchEvent(
            'ecomdev_sphinx_source_attribute_active_options',
            ['proxy' => $proxy, 'attributes' => $attributes]
        );

        $this->_options = [];
        foreach ($attributes as $code => $attribute) {
            $this->_options[$code] = sprintf('%s (%s)', $attribute->getAttributeCode(), $attribute->getAttributeName());
        }

        if ($proxy->additionalAttributes) {
            $this->_options += $proxy->additionalAttributes;
        }

        return $this;
    }
}
