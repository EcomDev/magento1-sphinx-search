<?php

use EcomDev_Sphinx_Model_Sphinx_Facet_Filter_OptionInterface as OptionInterface;
use EcomDev_Sphinx_Model_Sphinx_FacetInterface as FacetInterface;
use EcomDev_Sphinx_Block_Layer_Facet_RendererInterface as RendererInterface;

class EcomDev_Sphinx_Block_Layer
    extends Mage_Core_Block_Template
    implements EcomDev_Sphinx_Block_LayerInterface
{

    /**
     * List of facets
     *
     * @var FacetInterface[]
     */
    protected $_facets;

    /**
     * List of active filters
     *
     * @var string[]
     */
    protected $_activeFacetFilters;

    /**
     * Current url for page
     * 
     * @var string
     */
    protected $_currentUrl;

    /**
     * Current additional parameters in url
     * 
     * @var string[]
     */
    protected $_additionalQuery;

    /**
     * Renderer types of the facet
     *
     * @var string[]
     */
    protected $_renderTypes = array();

    /**
     * Url builder for layered navigation
     *
     * @var EcomDev_SphinxSeo_Model_Url_Builder
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
     * Configuration model
     *
     * @return EcomDev_Sphinx_Model_Config
     */
    public function getConfig()
    {
        return Mage::getSingleton('ecomdev_sphinx/config');
    }
    
    /**
     * Returns available facets for filtration
     * 
     * @return EcomDev_Sphinx_Model_Sphinx_FacetInterface[]
     */
    public function getFacets()
    {
        if ($this->_facets === null) {
            $this->initFacets();
        }
        
        return $this->_facets; 
    }

    protected function initFacets()
    {
        $this->_facets = [];
        $this->_activeFacetFilters = [];
        foreach($this->getConfig()->getScope()->getFacets() as $code => $facet) {
            $this->_facets[$code] = $facet;
            $value = $facet->getFilterValue();
            if ($value !== false) {
                $this->_activeFacetFilters[$code] = $value;
            }
        }

        $this->urlBuilder->initFacets($this->_facets, $this->_activeFacetFilters);
        return $this;
    }

    /**
     * Returns current curl url
     *
     * @return string
     */
    public function getCurrentUrl()
    {
        if ($this->_facets === null) {
            $this->initFacets();
        }

        return $this->urlBuilder->getCurrentUrl();
    }

    /**
     * Returns additional query params from the current url
     *
     * @return string[]
     */
    public function getAdditionalQuery()
    {
        if ($this->_facets === null) {
            $this->initFacets();
        }

        return $this->urlBuilder->getAdditionalParams();
    }

    /**
     * Option url for a filter
     *
     * @param OptionInterface $option
     * @return string
     */
    public function getOptionUrl(OptionInterface $option)
    {
        $filter = $option->getFacet()->getFilterValue($option);
        $without = [];
        $options = [];
        if ($filter === false || $filter === null || $filter === '') {
            $without = [$option->getFacet()->getFilterField()];
        } else {
            $options[$option->getFacet()->getFilterField()] = $filter;
        }

        return $this->urlBuilder->getUrl($options, $without);
    }

    /**
     * Clear url for a filter
     * 
     * @param FacetInterface $facet
     * @return string
     */
    public function getClearUrl(FacetInterface $facet)
    {
        return $this->urlBuilder->getUrl([], [$facet->getFilterField()]);
    }
    
    /**
     * Current fileter values
     * 
     * @return string[]
     */
    public function getActiveFilters()
    {
        if ($this->_activeFacetFilters === null) {
            $this->initFacets();
        }
        
        return $this->_activeFacetFilters;
    }
    
    /**
     * Add renderer to available renderers list
     *
     * @param string $type
     * @param string $childBlockAlias
     * @return $this
     */
    public function addRenderer($type, $childBlockAlias)
    {
        $renderer = $this->getChild($childBlockAlias);
        if (!$renderer instanceof RendererInterface) {
            return $this;
        }

        if (empty($this->_renderTypes)) {
            $this->_renderTypes['default'] = $renderer;
        }

        $renderer->setLayer($this);
        $this->_renderTypes[$type] = $renderer;
        return $this;
    }

    /**
     * Returns a renderer associated with a facet
     *
     * @param FacetInterface $facet
     * @return RendererInterface
     */
    public function getRenderer(FacetInterface $facet)
    {
        if (empty($this->_renderTypes)) {
            Mage::throwException('There is no renderer set for a facet');
        }

        $type = $facet->getRenderType();

        if (isset($this->_renderTypes[$type])) {
            return $this->_renderTypes[$type];
        }

        return $this->_renderTypes['default'];
    }


}
