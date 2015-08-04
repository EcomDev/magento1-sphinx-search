<?php

use EcomDev_Sphinx_Model_Sphinx_FacetInterface as FacetInterface;

class EcomDev_Sphinx_Model_Source_Category_Filter_Type
    extends EcomDev_Sphinx_Model_Source_AbstractOption
{
    protected function _initOptions()
    {
        $this->_options = array(
            FacetInterface::RENDER_TYPE_LINK => $this->__('Tree Link Structure'),
            FacetInterface::RENDER_TYPE_OPTION => $this->__('Multiple Option')
        );
    }
}
