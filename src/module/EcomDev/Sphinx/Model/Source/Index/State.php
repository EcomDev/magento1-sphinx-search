<?php

class EcomDev_Sphinx_Model_Source_Index_State
    extends EcomDev_Sphinx_Model_Source_AbstractOption
{
    const STATE_NEW = 'new';
    const STATE_SYNCED = 'synced';
    const STATE_QUEUED = 'queued';

    protected function _initOptions()
    {
        $this->_options = array(
            self::STATE_NEW => $this->__('New'),
            self::STATE_SYNCED => $this->__('In Sync'),
            self::STATE_QUEUED => $this->__('Out of Sync'),
        );
    }
}
