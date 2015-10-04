<?php

class EcomDev_Sphinx_Block_Adminhtml_Keyword
    extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    protected function _prepareLayout()
    {
        $this->_headerText = $this->__('View Keywords');
        $this->_buttons = [];
        return Mage_Adminhtml_Block_Widget_Container::_prepareLayout();
    }
}
