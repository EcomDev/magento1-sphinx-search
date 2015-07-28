<?php

abstract class EcomDev_Sphinx_Model_Source_AbstractOption
{
    protected $_options;
    
    protected $_excludedOptions;
    
    abstract protected function _initOptions();

    /**
     * Retrieve options as key => value pairs
     *
     * @return array
     */
    public function getOptions()
    {
        if ($this->_options === null) {
            $this->_initOptions();
        }
        return $this->_options;
    }

    /**
     * Retrieves options as array for options grid
     *
     * @return array
     */
    public function toOptionHash()
    {
        if (is_array($this->_excludedOptions)) {
            return array_diff_key(
                $this->getOptions(),
                array_combine(
                    $this->_excludedOptions, 
                    $this->_excludedOptions
                )
            );
        }
        
        return $this->getOptions();
    }

    /**
     * Retrieves options as array for form fields
     *
     * @param bool $multiple
     * @return array
     */
    public function toOptionArray($multiple = false)
    {
        $options = array();
        
        if (!$multiple) {
            $options[] = array('value' => '', 'label' => '');
        }
        
        foreach ($this->getOptions() as $value => $label) {
            if (is_array($this->_excludedOptions) && in_array($value, $this->_excludedOptions, true)) {
                continue;
            }
            $options[] = array('value' => $value, 'label' => $label);
        }

        return $options;
    }

    /**
     * Sets options that should be excluded from filter
     * 
     * @param array $options
     * @return $this
     */
    public function setExcludedOptions(array $options)
    {
        $this->_excludedOptions = $options;
        return $this;
    }
    
    public function __($text)
    {
        $args = func_get_args();
        return call_user_func_array(
            array(Mage::helper('ecomdev_sphinx'), '__'),
            $args
        );
    }
}
