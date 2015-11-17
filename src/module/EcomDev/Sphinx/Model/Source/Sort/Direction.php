<?php

class EcomDev_Sphinx_Model_Source_Sort_Direction
    extends EcomDev_Sphinx_Model_Source_AbstractOption
{
    const DIRECTION_ASC = 'asc';
    const DIRECTION_DESC = 'desc';

    protected function _initOptions()
    {
        $this->_options = [
            self::DIRECTION_ASC => Mage::helper('ecomdev_sphinx')->__('Low to High'),
            self::DIRECTION_DESC => Mage::helper('ecomdev_sphinx')->__('High to Low'),
        ];
    }

}
