<?php

abstract class EcomDev_Sphinx_Block_Adminhtml_Grid
    extends Mage_Adminhtml_Block_Widget_Grid
{
    protected $_sortField = '';
    protected $_sortDirection = 'desc';
    protected $_filterVar = 'filter';
    protected $_prefix = 'object';
    protected $_objectId = '';
    
    /**
     * Initializes grid block
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId($this->_prefix . 'Grid');
        
        if ($this->_sortField) {
            $this->setDefaultSort($this->_sortField);
            $this->setDefaultDir($this->_sortDirection);
        }
        
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $this->setVarNameFilter($this->_filterVar);
    }

    /**
     * Prepare grid collection
     *
     * @return $this
     */
    protected function _prepareCollection()
    {
        $collection = $this->_getCollectionInstance();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }
    
    abstract protected function _getCollectionInstance();

    /**
     * Adds simple text column
     *
     * @param string $field
     * @param string $label
     * @param null|string|int $width
     * @param null|string $index
     * @throws Exception
     * @return $this
     */
    protected function _addTextColumn($field, $label, $width = null, $index = null)
    {
        if ($index === null) {
            $index = $field;
        }
        
        $this->addColumn($field, array(
            'header' => $label,
            'index' => $index,
            'width' => $width,
            'default' => '---'
        ));
        return $this;
    }

    /**
     * Adds options drop down column
     *
     * @param string $field
     * @param string $label
     * @param string|object $optionsModel
     * @param null|string|int $width
     * @param null|string $index
     * @throws Exception
     * @return $this
     */
    protected function _addOptionsColumn($field, $label, $optionsModel, $width = null, $index = null, $callback = null)
    {
        if (is_string($optionsModel)) {
            $optionsModel = Mage::getSingleton($optionsModel);
        }

        if ($index === null) {
            $index = $field;
        }
        
        $this->addColumn($field, array(
            'header' => $label,
            'index' => $index,
            'width' => $width,
            'type' => 'options',
            'options' => $optionsModel->toOptionHash(),
            'frame_callback' => $callback
        ));
        return $this;
    }

    /**
     * Adds a column that is rendered as store
     * 
     * @param string $field
     * @param string $label
     * @param null|string $width
     * @param null|string $index
     * @return $this
     * @throws Exception
     */
    protected function _addStoreColumn($field, $label, $width = null, $index = null)
    {
        if ($index === null) {
            $index = $field;
        }
        
        $this->addColumn($field, array(
            'header' => $label,
            'index' => $index,
            'width' => $width,
            'type' => 'store',
            'skipAllStoresLabel' => true
        ));
        
        return $this;
    }

    /**
     * Adds a new action column
     * 
     * @param string $label
     * @param array $actionHash
     * @param string $columnId
     * @param string $width
     * @return $this
     * @throws Exception
     */
    protected function _addActionColumn($label, array $actionHash, $columnId = 'action', $width = '50px')
    {
        $actions = array();
        foreach ($actionHash as $key => $actionLabel) {
            $actions[] = array(
                'caption' => $actionLabel,
                'url'     => array(
                    'base' => (strpos($key, '/') !== false ? $key : '*/*/' . $key ),
                ),
                'field'   => $this->_objectId
            );
        }
        
        $this->addColumn($columnId, array(
            'header'    => $label,
            'width'     => $width,
            'type'      => 'action',
            'getter'     => 'getId',
            'actions'   => $actions,
            'filter' => false,
            'sortable' => false
        ));
        
        return $this;
    }

    /**
     * Adds simple text column
     *
     * @param string $field
     * @param string $label
     * @param null|string|int $width
     * @param string|null $index
     * @return $this
     * @throws Exception
     */
    protected function _addNumberColumn($field, $label, $width = null, $index = null)
    {
        if ($index === null) {
            $index = $field;
        }
        
        $this->addColumn($field, array(
            'header' => $label,
            'index' => $index,
            'width' => $width,
            'type'  => 'number' 
        ));
        return $this;
    }

    /**
     * Adds simple text column
     *
     * @param string $field
     * @param string $label
     * @param null|string|int $width
     * @param string|null $index
     * @return $this
     * @throws Exception
     */
    protected function _addDateTimeColumn($field, $label, $width = null, $index = null, $callback = null)
    {
        if ($index === null) {
            $index = $field;
        }
        
        if ($width === null) {
            $width = '120px';
        }

        $this->addColumn($field, array(
            'header' => $label,
            'index' => $index,
            'width' => $width,
            'type' => 'datetime',
            'frame_callback' => $callback
        ));
        
        return $this;
    }

    /**
     * Retrieve grid ajax reload url
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current'=>true));
    }

    /**
     * Retrieve grid edit row url
     *
     * @param Varien_Object $row
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array(
            'store' => $this->getRequest()->getParam('store'),
            $this->_objectId => $row->getId()
        ));
    }
}
