<?php

class EcomDev_Sphinx_Block_Adminhtml_Attribute_Edit
    extends EcomDev_Sphinx_Block_Adminhtml_Edit_Form_Container
{

    /**
     * Object identifier field name in request
     *
     * @var string
     */
    protected $_objectId = 'attribute_id';

    /**
     * Object name field in request
     *
     * @var string
     */
    protected $_objectName = 'attribute_code';
    
    /**
     * Returns new header label
     *
     * @return string
     */
    protected function _getNewHeaderLabel()
    {
        return $this->__('Add New Attribute To Sphinx Index');
    }

    /**
     * Returns edit header label
     *
     * @param string $name
     * @return string
     */
    protected function _getEditHeaderLabel($name)
    {
        return $this->__('Edit Sphinx Attribute Options "%s"', $name);
    }

    /**
     * Returns save action name
     *
     * @return string
     */
    protected function _getSaveActionName()
    {
        if (!$this->getObject()->getId()) {
            return 'create';
        }
        
        return parent::_getSaveActionName();
    }
}
