<?php

class EcomDev_Sphinx_Block_Adminhtml_Configure_Grid
    extends EcomDev_Sphinx_Block_Adminhtml_Grid
{
    protected $_sortField = 'code';
    protected $_sortDirection = 'desc';
    protected $_prefix = 'sphinx_configure';
    protected $_objectId = 'code';
    
    public function __construct()
    {
        parent::__construct();
        $this->_filterVisibility = false;
        $this->_pagerVisibility  = false;
    }

    /**
     * Returns a collection for grid
     * 
     * @return EcomDev_Sphinx_Model_Resource_Sphinx_Config_Index_Collection
     */
    protected function _getCollectionInstance()
    {
        return Mage::getResourceModel('ecomdev_sphinx/sphinx_config_index_collection');
    }

    /**
     * Prepares grid columns
     *
     * @return $this
     */
    protected function _prepareColumns()
    {
        $this->_addTextColumn('code', $this->__('Index'));

        $this->_addOptionsColumn(
            'state', $this->__('State'), 'ecomdev_sphinx/source_index_state', '100px', null,
            array($this, 'decorateStatus')
        );
        
        $this->_addNumberColumn('pending_rows', $this->__('# Pending'), '100px');
        $this->_addNumberColumn('indexed_rows', $this->__('# Indexed'), '100px');

        $this->_addDateTimeColumn(
            'current_reindex_at', $this->__('Recent Reindex Time'), '200px', null, array($this, 'decorateDate')
        );
        $this->_addDateTimeColumn(
            'previous_reindex_at', $this->__('Previous Reindex Time'), '200px', null, array($this, 'decorateDate')
        );
        
        foreach ($this->getColumns() as $column) {
            $column->setSortable(false);
        }
        
        return parent::_prepareColumns();
    }

    /**
     * Decorate status column values
     *
     * @param string $value
     * @param Varien_Object $row
     * @param Mage_Adminhtml_Block_Widget_Grid_Column $column
     * @param bool $isExport
     *
     * @return string
     */
    public function decorateStatus($value, $row, $column, $isExport)
    {
        $class = '';
        switch ($row->getState()) {
            case EcomDev_Sphinx_Model_Source_Index_State::STATE_NEW:
                $class = 'grid-severity-critical';
                break;
            case EcomDev_Sphinx_Model_Source_Index_State::STATE_QUEUED:
                $class = 'grid-severity-major';
                break;
            case EcomDev_Sphinx_Model_Source_Index_State::STATE_SYNCED :
                $class = 'grid-severity-notice';
                break;
        }
        
        return '<span class="'.$class.'"><span>'.$value.'</span></span>';
    }

    /**
     * Decorate last run date coumn
     *
     * @param string $value
     * @param Varien_Object $row
     * @param Mage_Adminhtml_Block_Widget_Grid_Column $column
     * @param bool $isExport
     *
     * @return string
     */
    public function decorateDate($value, $row, $column, $isExport)
    {
        if(!$value) {
            return $this->__('Never');
        }
        return $value;
    }
    
    /**
     * Retrieve grid edit row url
     *
     * @param Varien_Object $row
     * @return string
     */
    public function getRowUrl($row)
    {
        return false;
    }
}
