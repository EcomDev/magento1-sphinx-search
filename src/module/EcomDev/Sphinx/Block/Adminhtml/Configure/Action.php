<?php

class EcomDev_Sphinx_Block_Adminhtml_Configure_Action
    extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    protected function _prepareLayout()
    {
        $this->_headerText = $this->__('Sphinx Daemon Actions');
        $this->_buttons = array();
        return Mage_Adminhtml_Block_Widget_Container::_prepareLayout();
    }

    /**
     * Url to daemon control
     *
     * @return string
     */
    public function getDaemonControlUrl()
    {
        return $this->getUrl('*/*/controlDaemon');
    }

    /**
     * Url to index control
     *
     * @return string
     */
    public function getIndexControlUrl()
    {
        return $this->getUrl('*/*/controlIndex');
    }

    /**
     * Url to update and merge delta index
     * 
     * @return string
     */
    public function getIndexDeltaUrl()
    {
        return $this->getUrl('*/*/indexDelta');
    }
}
