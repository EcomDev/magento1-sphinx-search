<?php

class EcomDev_Sphinx_Model_Source_Level
    extends EcomDev_Sphinx_Model_Source_AbstractOption
{

    const LEVEL_DEFAULT = '0';
    const LEVEL_SAME = '1';
    const LEVEL_CUSTOM = '-1';

    protected function _initOptions()
    {
        $this->_options = [
            self::LEVEL_DEFAULT => $this->__('Default Level (Children)'),
            self::LEVEL_SAME => $this->__('Same Leaf Level (Neighbours)'),
            self::LEVEL_CUSTOM => $this->__('Custom Level')
        ];
    }

}
