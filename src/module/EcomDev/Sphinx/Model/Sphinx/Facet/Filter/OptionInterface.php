<?php

use EcomDev_Sphinx_Model_Sphinx_FacetInterface as FacetInterface;

/**
 * Option of the facet
 * 
 */
interface EcomDev_Sphinx_Model_Sphinx_Facet_Filter_OptionInterface
{
    /**
     * Returns option label
     * 
     * @return string
     */
    public function getLabel();

    /**
     * Returns option value
     * 
     * @return string
     */
    public function getValue();

    /**
     * Returns number of mentions
     * 
     * @return int
     */
    public function getCount();

    /**
     * Returns true if option is active
     * 
     * @return bool
     */
    public function isActive();

    /**
     * Returns an associated facet
     * 
     * @return FacetInterface
     */
    public function getFacet();
}
