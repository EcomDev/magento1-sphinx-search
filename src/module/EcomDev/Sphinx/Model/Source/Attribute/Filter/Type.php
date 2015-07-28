<?php

class EcomDev_Sphinx_Model_Source_Attribute_Filter_Type
    extends EcomDev_Sphinx_Model_Source_AbstractOption
{
    const TYPE_SINGLE = 'select';
    const TYPE_MULTIPLE = 'multiple';
    const TYPE_SLIDER = 'slider';
    const TYPE_RANGE = 'range';
    
    protected function _initOptions()
    {
        $this->_options = array(
            self::TYPE_SINGLE => $this->__('Single Option'),
            self::TYPE_MULTIPLE => $this->__('Multiple Option'),
            self::TYPE_SLIDER => $this->__('Slider'),
            self::TYPE_RANGE => $this->__('Range')
        );
    }
}
