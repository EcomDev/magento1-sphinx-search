<?php

use EcomDev_Sphinx_Model_Sphinx_FacetInterface as FacetInterface;
use EcomDev_Sphinx_Block_Layer_Facet_RendererInterface as RendererInterface;

/**
 * Layer block interface
 *
 */
interface EcomDev_Sphinx_Block_LayerInterface
{
    /**
     * Add renderer to available renderers list
     *
     * @param string $type
     * @param string $childBlockAlias
     * @return $this
     */
    public function addRenderer($type, $childBlockAlias);

    /**
     * Returns a renderer associated with a facet
     *
     * @param FacetInterface $facet
     * @return RendererInterface
     */
    public function getRenderer(FacetInterface $facet);

    /**
     * Return current url of the page, without query params
     *
     * @return string
     */
    public function getCurrentUrl();

    /**
     * Returns list of additional query
     *
     * @return string[]
     */
    public function getAdditionalQuery();

    /**
     * Returns list of active filters for layer
     *
     * @return string[]
     */
    public function getActiveFilters();

    /**
     * Returns list of facets
     *
     * @return FacetInterface[]
     */
    public function getFacets();
}
