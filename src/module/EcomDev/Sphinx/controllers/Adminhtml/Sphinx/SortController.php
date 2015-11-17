<?php

class EcomDev_Sphinx_Adminhtml_Sphinx_SortController
    extends EcomDev_Sphinx_Controller_Adminhtml
{
    protected $_prefix = 'sphinx_sort';
    protected $_idField = 'sort_id';
    protected $_menu = 'catalog/sphinx/sort';

    /**
     * Returns an instance of assortment model
     *
     * @return EcomDev_Sphinx_Model_Scope
     */
    protected function _getModel()
    {
        return Mage::getModel('ecomdev_sphinx/sort');
    }
    
    /**
     * Initializes admin titles
     *
     * @param null|EcomDev_Sphinx_Model_Sort $currentObject
     * @return string
     */
    protected function _initTitles(EcomDev_Sphinx_Model_AbstractModel $currentObject = null)
    {
        $this->_title($this->__('Catalog'))
            ->_title($this->__('Sphinx Search'))
            ->_title($this->__('Manage Sort Orders'));

        if ($currentObject !== null) {
            $this->_title(
                $currentObject->getId() ?
                    $this->__('Edit Sort Order "%s"', $currentObject->getCode()) :
                    $this->__('New Sort Order')
            );
        }

        return $this;
    }

    /**
     * Returns object title for error messages
     *
     * @return string
     */
    protected function _getObjectTitle()
    {
        return $this->__('Sort Order');
    }
}
