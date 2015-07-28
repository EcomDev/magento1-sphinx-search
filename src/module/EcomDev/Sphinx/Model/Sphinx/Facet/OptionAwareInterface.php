<?php

/**
 * Option Aware Facet
 * 
 * This one is used for pure Magento option type attributes
 */
interface EcomDev_Sphinx_Model_Sphinx_Facet_OptionAwareInterface
    extends EcomDev_Sphinx_Model_Sphinx_FacetInterface
{
    /**
     * Returns all available option ids
     * 
     * @return mixed
     */
    public function getOptionIds();

    /**
     * Sets list of option labels by identifier
     * 
     * @param string[] $optionLabel
     * @param string[] $sortOrder
     * @return $this
     */
    public function setOptionLabel(array $optionLabel, array $sortOrder);
}
