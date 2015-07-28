<?php

class EcomDev_Sphinx_Block_Adminhtml_Configure
    extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    protected function _prepareLayout()
    {
        $this->_headerText = $this->__('Sphinx Daemon Status');
        $this->_buttons = array();
        return Mage_Adminhtml_Block_Widget_Container::_prepareLayout();
    }
}
