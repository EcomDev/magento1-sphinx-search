<?php

class EcomDev_Sphinx_Model_Source_Attribute_Active
    extends EcomDev_Sphinx_Model_Source_AbstractOption
{

    protected function _initOptions()
    {
        $attributes = Mage::getSingleton('ecomdev_sphinx/config')->getActiveAttributes();

        $this->_options = array();
        foreach ($attributes as $code => $attribute) {
            $this->_options[$code] = $attribute->getAttributeName();
        }

        return $this;
    }
}
