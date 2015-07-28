<?php

class EcomDev_Sphinx_Model_Source_Yesno
    extends EcomDev_Sphinx_Model_Source_Default
{
    public function __construct()
    {
        $this->setSourceModel('adminhtml/system_config_source_yesno');
    }
}
