<?php

class EcomDev_Sphinx_Block_Adminhtml_Scope_Edit_Form
    extends EcomDev_Sphinx_Block_Adminhtml_Edit_Form
{

    protected $_fieldNameSuffix = 'sphinx_scope';
    
    protected function _createFields()
    {
        $this->_addFieldset('config', $this->__('Configuration'));
        $this->_addField('configuration', 'textarea', $this->__('Configuration Json'), array('required' => true));
        return $this;
    }

    /**
     * Sets values for fields from data object
     *
     * @return $this
     */
    protected function _setFieldValues()
    {
        $configuration = $this->getDataObject()->getConfiguration();
        
        if (!is_array($configuration)) {
            $configuration = array();
        }
        
        $currentConfiguration = json_encode($configuration);
        
        if($this->getDataObject()->getFailedConfiguration()) {
            $currentConfiguration = $this->getDataObject()->getFailedConfiguration();
        } elseif ($this->getDataObject()->getOriginalConfiguration()) {
            $currentConfiguration = $this->getDataObject()->getOriginalConfiguration();
        }
        
        $this->getForm()->setValues(array('configuration' => $currentConfiguration));
        return $this;
    }
}
