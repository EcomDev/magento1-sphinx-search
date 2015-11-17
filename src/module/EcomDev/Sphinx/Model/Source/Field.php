<?php

class EcomDev_Sphinx_Model_Source_Field
    extends EcomDev_Sphinx_Model_Source_AbstractOption
{
    protected function _initOptions()
    {
        $this->_options = [];
        foreach (Mage::getSingleton('ecomdev_sphinx/config')->getVirtualFields() as $field) {
            $this->_options[$field->getCode()] = $field->getName();
        }
    }

}
