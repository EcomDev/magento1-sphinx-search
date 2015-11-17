<?php

class EcomDev_Sphinx_Block_Adminhtml_Sort_Edit
    extends EcomDev_Sphinx_Block_Adminhtml_Edit_Form_Container
{
    /**
     * Object identifier field name in request
     *
     * @var string
     */
    protected $_objectId = 'scope_id';

    /**
     * Object name field in request
     *
     * @var string
     */
    protected $_objectName = 'name';

    /**
     * Returns new header label
     *
     * @return string
     */
    protected function _getNewHeaderLabel()
    {
        return $this->__('Add New Sort Order');
    }

    /**
     * Returns edit header label
     *
     * @param string $name
     * @return string
     */
    protected function _getEditHeaderLabel($name)
    {
        return $this->__('Edit Sort Order "%s"', $name);
    }
}
