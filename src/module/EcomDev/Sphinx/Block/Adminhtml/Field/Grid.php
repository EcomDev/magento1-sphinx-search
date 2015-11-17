<?php

class EcomDev_Sphinx_Block_Adminhtml_Field_Grid
    extends EcomDev_Sphinx_Block_Adminhtml_Grid
{
    protected $_sortField = 'field_id';
    protected $_sortDirection = 'desc';
    protected $_filterVar = 'sphinx_field_filter';
    protected $_prefix = 'sphinx_field';
    protected $_objectId = 'field_id';
    
    protected function _getCollectionInstance()
    {
        return Mage::getModel('ecomdev_sphinx/field')->getCollection();
    }

    /**
     * Prepares grid columns
     *
     * @return $this
     */
    protected function _prepareColumns()
    {
        $this->_addNumberColumn('field_id', $this->__('ID'), '50px');
        $this->_addTextColumn('code', $this->__('Code'));
        $this->_addTextColumn('name', $this->__('Name'));
        $this->_addOptionsColumn('is_sort', $this->__('Is Sortable'), 'ecomdev_sphinx/source_yesno', '100px');
        $this->_addOptionsColumn('is_active', $this->__('Is Active'), 'ecomdev_sphinx/source_yesno', '100px');
        $this->_addTextColumn('position', $this->__('Position'));
        $this->_addActionColumn($this->__('Action'), array('edit' => $this->__('Edit')));

        return parent::_prepareColumns();
    }
}
