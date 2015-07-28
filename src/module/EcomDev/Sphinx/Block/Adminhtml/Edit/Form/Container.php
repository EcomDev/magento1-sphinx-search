<?php

abstract class EcomDev_Sphinx_Block_Adminhtml_Edit_Form_Container
    extends Mage_Adminhtml_Block_Widget_Form_Container
{
    /**
     * Object identifier field name in request
     *
     * @var string
     */
    protected $_objectId = '';

    /**
     * Object name field in request
     * 
     * @var string
     */
    protected $_objectName = '';

    /**
     * Saving controller name
     *
     * @var string
     */
    protected $_controller = 'object';
    
    protected $_saveActionName = 'save';

    /**
     * Disable auto creation of blocks in parent::_prepareLayout()
     */
    protected $_mode = false;
    protected $_blockGroup = false;

    /**
     * Retrieve current brand model
     *
     * @return EcomDev_Sphinx_Model_AbstractModel
     */
    public function getObject()
    {
        return Mage::registry('current_object');
    }

    /**
     * Retrieve edit block header
     *
     * @return string
     */
    public function getHeaderText()
    {
        if ($this->getObject()->getId()) {
            return $this->_getEditHeaderLabel($this->getObject()->getDataUsingMethod($this->_objectName));
        }

        return $this->_getNewHeaderLabel();
    }

    /**
     * Returns new header label
     * 
     * @return string
     */
    abstract protected function _getNewHeaderLabel();

    /**
     * Returns edit header label
     * 
     * @param string $name
     * @return string
     */
    abstract protected function _getEditHeaderLabel($name);
    
    /**
     * Get form action URL
     *
     * @see parent::getFormActionUrl()
     * @return string
     */
    public function getFormActionUrl()
    {
        return $this->getUrl('*/*/' . $this->_getSaveActionName(), array('_current' => true));
    }

    /**
     * Returns save action name
     * 
     * @return string
     */
    protected function _getSaveActionName()
    {
        return $this->_saveActionName;
    }
}
