<?php

class EcomDev_Sphinx_Block_Adminhtml_Attribute
    extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    protected function _prepareLayout()
    {
        $this->_headerText = $this->__('Manage Sphinx Attributes');
        return Mage_Adminhtml_Block_Widget_Container::_prepareLayout();
    }
}
