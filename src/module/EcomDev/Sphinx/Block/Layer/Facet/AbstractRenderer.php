<?php

use EcomDev_Sphinx_Model_Sphinx_FacetInterface as FacetInterface;
use EcomDev_Sphinx_Block_LayerInterface as LayerInterface;

abstract class EcomDev_Sphinx_Block_Layer_Facet_AbstractRenderer
    extends Mage_Core_Block_Template
    implements EcomDev_Sphinx_Block_Layer_Facet_RendererInterface
{
    /**
     * Url builder instance
     *
     * @var EcomDev_Sphinx_Model_Url_Builder
     */
    protected $urlBuilder;

    /**
     * Initialize factory instance
     *
     * @param array $args
     */
    public function __construct(array $args)
    {
        parent::__construct($args);
        $this->urlBuilder = Mage::getSingleton('ecomdev_sphinx/url_builder');
    }


    /**
     * Returns html identifier for container
     *
     */
    public function getHtmlId()
    {
        return sprintf('%s.%s', $this->getNameInLayout(), $this->getFacet()->getFilterField());
    }

    /**
     * Returns a facet instance
     *
     * @return FacetInterface
     */
    public function getFacet()
    {
        return $this->_getData('facet');
    }

    /**
     * Returns a layer block instance
     *
     * @return LayerInterface
     */
    public function getLayer()
    {
        return $this->_getData('layer');
    }

    /**
     * Returns a facet label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->getFacet()->getLabel();
    }

    /**
     * Returns a facet code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->getFacet()->getFilterField();
    }

    /**
     * Sets a layer block into the current block for usage in own methods
     *
     * @param LayerInterface $layer
     * @return $this
     */
    public function setLayer(LayerInterface $layer)
    {
        $this->setData('layer', $layer);
        return $this;
    }

    /**
     * A callback after facet has been set
     *
     * @return $this
     */
    protected function _initRender()
    {
        return $this;
    }

    /**
     * Returns a rendered html of facet instance
     *
     * @param FacetInterface $facet
     * @return string
     */
    public function render(FacetInterface $facet)
    {
        $this->setData('facet', $facet);
        $this->_initRender();
        return $this->toHtml();
    }

    /**
     * Returns a true if there is any value selected for this filter
     *
     * @param EcomDev_Sphinx_Model_Sphinx_FacetInterface $facet
     * @return bool
     */
    public function isSelected(FacetInterface $facet)
    {
        $activeFilters = $this->getLayer()->getActiveFilters();
        return isset($activeFilters[$facet->getFilterField()]);
    }

    /**
     * Returns a true if there filter should be visible or not
     *
     * Is selected call is used to prevent situation,
     * if some value is already selected
     *
     * @param EcomDev_Sphinx_Model_Sphinx_FacetInterface $facet
     * @return bool
     */
    public function isVisible(FacetInterface $facet)
    {
        return $facet->isVisible() || $this->isSelected($facet);
    }

    /**
     * Clear url for a filter
     *
     * @param FacetInterface $facet
     * @param string[] $without
     * @param bool $withRel
     * @return string
     */
    public function getClearUrl(FacetInterface $facet, $without = [], $withRel = false)
    {
        return $this->urlBuilder->getUrl(
            [],
            array_merge($without, [$facet->getFilterField()]),
            true,
            $withRel
        );
    }
}
