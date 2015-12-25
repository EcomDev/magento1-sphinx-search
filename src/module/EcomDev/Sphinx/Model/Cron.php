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

    /**
     * Returns a lock model
     *
     * @return EcomDev_Sphinx_Model_Lock
     */
    protected function _getLock()
    {
        return Mage::getSingleton('ecomdev_sphinx/lock');
    }

    public function checkIndex()
    {
        if ($this->_getLock()->lock()) {
            $this->_getModel()->controlIndex();
        }
    }

    public function updateDeltas()
    {
        if ($this->_getLock()->lock()) {
            $this->_getModel()->controlIndexData();
        }
    }

    public function fullReindex()
    {
        if ($this->_getLock()->lock()) {
            $this->_getModel()->controlIndex(true);
        }
    }

    public function validateCategoryChanges()
    {
        if ($this->_getLock()->lock()) {
            Mage::getModel('ecomdev_sphinx/update')->notify('category');
        }
    }

    public function validateProductChanges()
    {
        if ($this->_getLock()->lock()) {
            Mage::getModel('ecomdev_sphinx/update')->notify('product');
        }
    }
}
