<?php

class EcomDev_Sphinx_Block_Adminhtml_Keyword_Grid
    extends EcomDev_Sphinx_Block_Adminhtml_Grid
{
    protected $_sortField = 'keyword';
    protected $_sortDirection = 'asc';
    protected $_filterVar = 'sphinx_keyword_filter';
    protected $_prefix = 'sphinx_keyword';
    protected $_objectId = 'keyword';

    protected function _getCollectionInstance()
    {
        return Mage::getModel('ecomdev_sphinx/index_keyword')->getCollection();
    }

    /**
     * Prepares grid columns
     *
     * @return $this
     */
    protected function _prepareColumns()
    {
        $this->_addTextColumn('keyword', $this->__('Keyword'), '50px');
        $this->_addOptionsColumn('store_id', $this->__('Store View'), 'ecomdev_sphinx/source_store', '100px');
        $this->_addTextColumn('trigram_list', $this->__('Trigrams'));
        $this->_addNumberColumn('frequency', $this->__('Frequency'), '50px');

        return parent::_prepareColumns();
    }

    /**
     * Retrieve grid edit row url
     *
     * @param Varien_Object $row
     * @return string
     */
    public function getRowUrl($row)
    {
        return '';
    }
}

