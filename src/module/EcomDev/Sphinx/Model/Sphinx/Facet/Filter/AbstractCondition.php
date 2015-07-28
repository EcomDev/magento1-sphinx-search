<?php

use EcomDev_Sphinx_Model_Sphinx_FacetInterface as FacetInterface;

/**
 * Abstract condition implementation
 */
abstract class EcomDev_Sphinx_Model_Sphinx_Facet_Filter_AbstractCondition
    implements EcomDev_Sphinx_Model_Sphinx_Facet_Filter_ConditionInterface
{
    /**
     * @var FacetInterface
     */
    protected $_facet;

    /**
     * Sets a facet into condition
     * 
     * @param FacetInterface $facet
     */
    public function __construct(FacetInterface $facet)
    {
        $this->_facet = $facet;
    }

    /**
     * Returns a condition facet
     * 
     * @return FacetInterface
     */
    public function getFacet()
    {
        return $this->_facet;
    }
}
