<?php

abstract class EcomDev_Sphinx_Model_Sphinx_AbstractConfig
{

    /**
     * An instance of resource model
     * 
     * @var Mage_Core_Model_Resource_Db_Abstract
     */
    protected $_resourceModel;
    
    /**
     * Returns an instance of configuration model of sphinx
     * 
     * @return EcomDev_Sphinx_Model_Config
     */
    protected function _getConfig()
    {
        return Mage::getSingleton('ecomdev_sphinx/config');
    }

    /**
     * A resource model for configuration fetch
     * 
     * @return Mage_Core_Model_Resource_Db_Abstract
     */
    public function getResource()
    {
        if ($this->_resourceModel === null 
            || (is_string($this->_resourceModel) && !Mage::getResourceSingleton($this->_resourceModel))) {
            throw new RuntimeException('Resource model alias should be specified');
        }
        
        if (is_string($this->_resourceModel)) {
            $this->_resourceModel = Mage::getResourceSingleton($this->_resourceModel);
        }
        
        return $this->_resourceModel;
    }

    /**
     * Renders strings in sphinx configuration file
     * 
     * @param string[] $strings
     * @param int $level
     * @return string
     */
    protected function _renderStrings($strings, $level = 1) 
    {
        $data = array();
        
        foreach ($strings as $string) {
            $lines = explode("\n", $string);
            $padding = str_pad('', 4 * $level, ' ');
            
            foreach ($lines as $lineIndex => $line) {
                if ($lineIndex > 0) {
                    $lines[$lineIndex] = $padding . str_pad('', 4, ' ') . $line;
                }
            }
            
            $data[] = $padding . implode("\\\n", $lines);
        }
        
        return implode("\n", $data);
    }
}
