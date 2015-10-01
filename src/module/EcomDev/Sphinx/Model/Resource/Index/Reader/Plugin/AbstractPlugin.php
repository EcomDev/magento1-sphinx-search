<?php

abstract class EcomDev_Sphinx_Model_Resource_Index_Reader_Plugin_AbstractPlugin
    extends Mage_Core_Model_Resource_Db_Abstract
    implements EcomDev_Sphinx_Contract_Reader_PluginInterface
{
    /**
     * Resource initialization
     *
     */
    protected function _construct()
    {
        $this->_setResource('ecomdev_sphinx');
    }
}
