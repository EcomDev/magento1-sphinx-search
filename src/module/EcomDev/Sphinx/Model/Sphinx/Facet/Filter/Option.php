<?php

use EcomDev_Sphinx_Model_Sphinx_FacetInterface as FacetInterface;

/**
 * Filter option item
 */
class EcomDev_Sphinx_Model_Sphinx_Facet_Filter_Option
    implements EcomDev_Sphinx_Model_Sphinx_Facet_Filter_OptionInterface
{
    /**
     * Facet to which this option is applied
     * 
     * @var FacetInterface
     */
    protected $_facet;

    /**
     * Label
     * 
     * @var string
     */
    protected $_label;

    /**
     * Option value (for filter apply)
     * 
     * @var string
     */
    protected $_value;

    /**
     * Number of matches
     * 
     * @var int
     */
    protected $_count;

    /**
     * Configures our facet filter option
     * 
     * @param FacetInterface $facet
     * @param array $info
     */
    public function __construct(FacetInterface $facet, array $info)
    {
        $this->_facet = $facet;
        
        if (isset($info['label'])) {
            $this->_label = $info['label'];
        }

        $this->_value = $info['value'];

        if (!isset($info['count'])) {
            $info['count'] = 0;
        }

        $this->_count = $info['count'];
    }
    
    /**
     * Returns option label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->_label;
    }

    /**
     * Returns option value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->_value;
    }

    /**
     * Returns number of mentions
     *
     * @return int
     */
    public function getCount()
    {
        return $this->_count;
    }

    /**
     * Returns true if option is active
     *
     * @return bool
     */
    public function isActive()
    {
        return $this->getFacet()->isOptionActive($this);
    }

    /**
     * Returns an associated facet
     *
     * @return FacetInterface
     */
    public function getFacet()
    {
        return $this->_facet;
    }
}
