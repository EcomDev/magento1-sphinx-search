<?php

class EcomDev_Sphinx_Block_Adminhtml_Attribute_Grid
    extends EcomDev_Sphinx_Block_Adminhtml_Grid
{
    protected $_sortField = 'attribute_code';
    protected $_sortDirection = 'asc';
    protected $_filterVar = 'sphinx_attribute_filter';
    protected $_prefix = 'sphinx_attribute';
    protected $_objectId = 'attribute_id';
    
    protected function _getCollectionInstance()
    {
        return Mage::getModel('ecomdev_sphinx/attribute')->getCollection();
    }

    /**
     * Prepares grid columns
     *
     * @return $this
     */
    protected function _prepareColumns()
    {
        $this->_addTextColumn('attribute_code', $this->__('Attribute Code'), '50px');
        $this->_addTextColumn('attribute_name', $this->__('Attribute Name'), '50px');
        $this->_addTextColumn('backend_type', $this->__('Attribute Type'), '50px');

        $this->_addOptionsColumn('is_layered', $this->__('Is Layered View'), 'ecomdev_sphinx/source_yesno', '100px');
        $this->_addOptionsColumn('filter_type', $this->__('Filter Type'), 'ecomdev_sphinx/source_attribute_filter_type', '100px');
        $this->_addOptionsColumn('is_custom_value_allowed', $this->__('Is Active'), 'ecomdev_sphinx/source_yesno', '100px');
        $this->_addOptionsColumn('is_fulltext', $this->__('Is Fulltext Search'), 'ecomdev_sphinx/source_yesno', '100px');
        $this->_addOptionsColumn('is_system', $this->__('Is System'), 'ecomdev_sphinx/source_yesno', '100px');
        $this->_addOptionsColumn('is_active', $this->__('Is Active'), 'ecomdev_sphinx/source_yesno', '100px');

        $this->_addActionColumn($this->__('Action'), array('edit' => $this->__('Edit')));

        return parent::_prepareColumns();
    }
}
