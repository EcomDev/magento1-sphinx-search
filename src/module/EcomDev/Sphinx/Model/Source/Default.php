<?php

class EcomDev_Sphinx_Model_Source_Default
    extends EcomDev_Sphinx_Model_Source_AbstractOption
{
    protected $_sourceModel;
    
    public function setSourceModel($sourceModel)
    {
        $this->_sourceModel = $sourceModel;
        return $this;
    }
    
    public function getSourceModel()
    {
        return $this->_sourceModel;
    }

    protected function _initOptions()
    {
        $this->_options = array();
        foreach (Mage::getSingleton($this->getSourceModel())->toOptionArray() as $option) {
            if ($option['value'] !== '') {
                $this->_options[$option['value']] = $option['label'];
            }
        }
    }
}
