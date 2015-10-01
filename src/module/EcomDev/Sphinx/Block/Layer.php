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
            $this->_facets = array();
            foreach($this->getConfig()->getScope()->getFacets() as $code => $facet) {
                $this->_facets[$code] = $facet;
            }
        }
        
        return $this->_facets; 
    }

    /**
     * Returns current curl url
     *
     * @return string
     */
    public function getCurrentUrl()
    {
        $this->initUrl();
        return $this->_currentUrl;
    }

    /**
     * Initializes current url
     *
     * @return $this
     */
    private function initUrl()
    {
        if ($this->_currentUrl !== null) {
            return $this;
        }

        $url = Mage::app()->getStore()->getCurrentUrl();
        $parsedUrl = parse_url($url);
        $queryString = '';
        if (isset($parsedUrl['query'])) {
            $queryString = $parsedUrl['query'];
            unset($parsedUrl['query']);
        }

        if (isset($parsedUrl['fragment'])) {
            unset($parsedUrl['fragment']);
        }

        if ($queryString) {
            parse_str(str_replace('&amp;', '&', $queryString), $queryParams);
        } else {
            $queryParams = array();
        }

        foreach (array('___SID', '___store', '___from_store', 'p') as $field) {
            if (isset($queryParams[$field])) {
                unset($queryParams[$field]);
            }
        }

        $this->_additionalQuery = $queryParams;
        $this->_currentUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host']
            . (isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '')
            . $parsedUrl['path'];

        return $this;
    }

    /**
     * Returns additional query params from the current url
     *
     * @return string[]
     */
    public function getAdditionalQuery()
    {
        $this->initUrl();
        return $this->_additionalQuery;
    }

    /**
     * Option url for a filter
     *
     * @param OptionInterface $option
     * @return string
     */
    public function getOptionUrl(OptionInterface $option)
    {
        $baseUrl = $this->getCurrentUrl();
        $activeFilters = $this->getActiveFilters() + $this->getAdditionalQuery();
        $filter = $option->getFacet()->getFilterValue($option);
        if ($filter === false || $filter === null || $filter === '') {
            unset($activeFilters[$option->getFacet()->getFilterField()]);
        } else {
            $activeFilters[$option->getFacet()->getFilterField()] = $filter;
        }

        return $baseUrl . ($activeFilters ? '?' . http_build_query($activeFilters, '', '&amp;') : '');
    }

    /**
     * Clear url for a filter
     * 
     * @param FacetInterface $facet
     * @return string
     */
    public function getClearUrl(FacetInterface $facet)
    {
        $baseUrl = $this->getCurrentUrl();
        
        $activeFilters = $this->getActiveFilters() + $this->_additionalQuery;
        
        if (isset($activeFilters[$facet->getFilterField()])) {
            unset($activeFilters[$facet->getFilterField()]);
        }
        
        return $baseUrl . ($activeFilters ? '?' . http_build_query($activeFilters, '', '&amp;') : '');
    }
    
    /**
     * Current fileter values
     * 
     * @return string[]
     */
    public function getActiveFilters()
    {
        if ($this->_activeFacetFilters === null) {
            $this->_activeFacetFilters = array();
            foreach ($this->getFacets() as $code => $facet) {
                $value = $facet->getFilterValue();
                if ($value !== false) {
                    $this->_activeFacetFilters[$code] = $value;
                }
            }
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
