<?php

class EcomDev_Sphinx_Model_Source_Store
    extends EcomDev_Sphinx_Model_Source_AbstractOption
{
    protected function _initOptions()
    {
        $this->_options = array();
        /* @var Mage_Core_Model_Store $store */
        foreach (Mage::app()->getStores(false) as $store) {
            $this->_options[$store->getId()] = $store->getName();
        }
    }
}
