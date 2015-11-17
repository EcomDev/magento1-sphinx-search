<?php

class EcomDev_Sphinx_Block_Adminhtml_Field
    extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    protected function _prepareLayout()
    {
        $this->_headerText = $this->__('Manage Virtual Fields');
        return Mage_Adminhtml_Block_Widget_Container::_prepareLayout();
    }
}
