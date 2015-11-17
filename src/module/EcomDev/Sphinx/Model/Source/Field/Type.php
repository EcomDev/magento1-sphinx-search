<?php

class EcomDev_Sphinx_Model_Source_Field_Type
    extends EcomDev_Sphinx_Model_Source_AbstractOption
{
    const TYPE_GROUPED = 'grouped';
    const TYPE_ALIAS = 'alias';
    const TYPE_RANGE = 'range';

    protected function _initOptions()
    {
        $this->_options = [
            self::TYPE_GROUPED => Mage::helper('ecomdev_sphinx')->__('Grouped'),
            self::TYPE_RANGE => Mage::helper('ecomdev_sphinx')->__('Range'),
            self::TYPE_ALIAS => Mage::helper('ecomdev_sphinx')->__('Alias')
        ];
    }

}
