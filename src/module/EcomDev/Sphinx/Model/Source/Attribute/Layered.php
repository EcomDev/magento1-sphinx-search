<?php

class EcomDev_Sphinx_Model_Source_Attribute_Layered
    extends EcomDev_Sphinx_Model_Source_AbstractOption
{
    const TYPE_SINGLE = 'select';
    const TYPE_MULTIPLE = 'multiple';
    const TYPE_SLIDER = 'slider';
    const TYPE_RANGE = 'range';

    protected function _initOptions()
    {
        $collection = Mage::getResourceSingleton('ecomdev_sphinx/attribute_collection')
            ->addFieldToFilter('is_layered', 1);

        $this->_options = array();
        foreach ($collection as $item) {
            $this->_options[$item->getId()] = $item->getAttributeName();
        }

        return $this;
    }
}
