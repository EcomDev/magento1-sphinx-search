<?php

class EcomDev_Sphinx_Model_Source_Sort_Order
    extends EcomDev_Sphinx_Model_Source_AbstractOption
{
    protected function _initOptions()
    {
        $this->_options = [];
        foreach (Mage::getSingleton('ecomdev_sphinx/config')->getSortOrders() as $sort) {
            $this->_options[$sort->getCode()] = $sort->getName();
        }
    }
}
