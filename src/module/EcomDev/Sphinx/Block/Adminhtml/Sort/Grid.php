<?php

class EcomDev_Sphinx_Block_Adminhtml_Sort_Grid
    extends EcomDev_Sphinx_Block_Adminhtml_Grid
{
    protected $_sortField = 'sort_id';
    protected $_sortDirection = 'desc';
    protected $_filterVar = 'sphinx_sort_filter';
    protected $_prefix = 'sphinx_sort';
    protected $_objectId = 'sort_id';
    
    protected function _getCollectionInstance()
    {
        return Mage::getModel('ecomdev_sphinx/sort')->getCollection();
    }

    /**
     * Prepares grid columns
     *
     * @return $this
     */
    protected function _prepareColumns()
    {
        $this->_addNumberColumn('sort_id', $this->__('ID'), '50px');
        $this->_addTextColumn('code', $this->__('Code'));
        $this->_addTextColumn('name', $this->__('Name'));
        $this->_addTextColumn('position', $this->__('Position'));
        $this->_addActionColumn($this->__('Action'), array('edit' => $this->__('Edit')));

        return parent::_prepareColumns();
    }
}
