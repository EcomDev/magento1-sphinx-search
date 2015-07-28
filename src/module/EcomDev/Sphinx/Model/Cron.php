<?php

/**
 * Sphinx cron tab
 * 
 */
class EcomDev_Sphinx_Model_Cron
{
    /**
     * Returns an instance of assortment model
     *
     * @return EcomDev_Sphinx_Model_Sphinx_Config
     */
    protected function _getModel()
    {
        return Mage::getModel('ecomdev_sphinx/sphinx_config');
    }

    public function checkIndex()
    {
        $this->_getModel()->controlIndex();
    }

    public function updateDeltas()
    {
        $this->_getModel()->controlIndexData();
    }

    public function fullReindex()
    {
        $this->_getModel()->controlIndex(true);
    }
}
