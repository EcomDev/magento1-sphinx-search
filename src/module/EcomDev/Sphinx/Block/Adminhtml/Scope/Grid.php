<?php

class EcomDev_Sphinx_Block_Adminhtml_Scope_Grid
    extends EcomDev_Sphinx_Block_Adminhtml_Grid
{
    protected $_sortField = 'scope_id';
    protected $_sortDirection = 'desc';
    protected $_filterVar = 'sphinx_scope_filter';
    protected $_prefix = 'sphinx_scope';
    protected $_objectId = 'scope_id';
    
    protected function _getCollectionInstance()
    {
        return Mage::getModel('ecomdev_sphinx/scope')->getCollection()
            ->joinParentName();
    }

    /**
     * Prepares grid columns
     *
     * @return $this
     */
    protected function _prepareColumns()
    {
        $this->_addNumberColumn('scope_id', $this->__('ID'), '50px');
        $this->_addNumberColumn('parent_id', $this->__('Parent ID'), '50px');
        $this->_addTextColumn('name', $this->__('Name'));
        $this->_addTextColumn('parent_name', $this->__('Parent Name'));

        $this->_addActionColumn($this->__('Action'), array('edit' => $this->__('Edit')));

        return parent::_prepareColumns();
    }
}
