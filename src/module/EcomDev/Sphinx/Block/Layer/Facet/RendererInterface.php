<?php

use EcomDev_Sphinx_Model_Sphinx_FacetInterface as FacetInterface;
use EcomDev_Sphinx_Block_LayerInterface as LayerInterface;

interface EcomDev_Sphinx_Block_Layer_Facet_RendererInterface
{
    /**
     * Returns a rendered html of facet instance
     *
     * @param FacetInterface $facet
     * @return string
     */
    public function render(FacetInterface $facet);

    /**
     * Returns a true if there filter should be visible or not
     *
     * @param FacetInterface $facet
     * @return bool
     */
    public function isVisible(FacetInterface $facet);

    /**
     * Sets a layer into a renderer
     *
     * @param LayerInterface $layer
     * @return $this
     */
    public function setLayer(LayerInterface $layer);

    /**
     * Returns true if any facet is selected
     *
     * @param FacetInterface $facet
     * @return bool
     */
    public function isSelected(FacetInterface $facet);
}
