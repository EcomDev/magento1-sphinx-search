<?php

class EcomDev_Sphinx_Model_Url_Builder
{
    /**
     * Current URL
     *
     * @var string
     */
    protected $currentUrl;

    /**
     * Additional Params
     *
     * @var string[]
     */
    protected $additionalParams;

    /**
     * Order of facets for url building
     *
     * @var int[]
     */
    protected $facetOrder = [];

    /**
     * Current filters
     *
     * @var string[]
     */
    protected $currentFilters = [];

    /**
     * Query parameters that are ignored during builing of current urls
     *
     * @var string[]
     */
    protected $ignoredQueryParams = ['___SID', '___store', 'p', 'dir', 'order'];

    /**
     * Separator of url
     *
     * @param array $options
     * @param array $without
     * @param bool $currentFilters
     * @param bool $withRel
     * @param string $separator
     * @return string|string[]
     */
    public function getUrl(
        array $options = [],
        array $without = [],
        $currentFilters = true,
        $withRel = false,
        $separator = '&amp;'
    )
    {
        if ($this->currentUrl === null) {
            $this->initUrl();
        }

        $query = $options;

        if ($currentFilters) {
            $query += $this->currentFilters;
        }

        $buildQuery = [];

        foreach (array_intersect_key($this->facetOrder, $query) as $facetCode => $order) {
            $buildQuery[$facetCode] = $query[$facetCode];
        }

        $buildQuery += array_diff_key($query, $this->facetOrder);

        $buildQuery += $this->additionalParams;

        if ($without) {
            $buildQuery = array_diff_key($buildQuery, array_combine($without, $without));
        }

        return $this->buildUrl($buildQuery, $withRel, $separator);
    }

    /**
     * Checks if no index no follow should be applied
     *
     * @param $query
     * @return string
     */
    public function getRel($query)
    {
        if (empty($query)) {
            return '';
        }

        return 'nofollow';
    }

    /**
     * Builds url with overridden query
     *
     * @param array $query
     * @param bool $withRel
     * @param string $separator
     * @return string
     */
    protected function buildUrl($query, $withRel = false, $separator = '&amp;')
    {
        if (!$this->currentUrl) {
            $this->initUrl();
        }

        $rel = $this->getRel($query);

        $url = $this->currentUrl . ($query ? '?' . http_build_query($query, '', $separator) : '');

        if ($withRel) {
            return [$url, $rel];
        }

        return $url;
    }

    /**
     * Initializes facets
     *
     * @param array $facets
     * @param array $activeFilters
     * @return $this
     */
    public function initFacets(array $facets, array $activeFilters)
    {
        $facetCodes = array_keys($facets);
        $this->facetOrder = array_combine($facetCodes, array_keys($facetCodes));
        $this->currentFilters = $activeFilters;
        return $this;
    }

    /**
     * Init current url for query builder
     *
     * @param null $url
     * @return $this
     */
    public function initUrl($url = null)
    {
        if ($url === null) {
            $url = Mage::app()->getStore()->getCurrentUrl(false);
        }

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

        foreach ($this->ignoredQueryParams as $field) {
            if (isset($queryParams[$field])) {
                unset($queryParams[$field]);
            }
        }

        // Do not use facets as additional query params
        $this->additionalParams = array_diff_key($queryParams, $this->facetOrder);

        $this->currentUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host']
            . (isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '')
            . $this->processPath($parsedUrl['path']);

        return $this;
    }

    /**
     * Init by category
     *
     * @param Mage_Catalog_Model_Category $category
     * @param string[] $facetCodes
     * @return $this
     */
    public function initByCategory($category, array $facetCodes = [])
    {
        $this->currentFilters = [];
        $this->facetOrder = array_combine($facetCodes, array_keys($facetCodes));
        
        $this->processFacetCodes($facetCodes);
        
        $categoryUrl = Mage::helper('catalog/category')->getCategoryUrl($category);
        $this->initUrl($categoryUrl);
        return $this;
    }

    /**
     * Callback for processing facet codes
     *
     * @param string[] $facetCodes
     *
     * @return $this
     */
    protected function processFacetCodes($facetCodes)
    {
        return $this;
    }

    /**
     * Returns current url without query params
     *
     * @return string
     */
    public function getCurrentUrl()
    {
        if ($this->currentUrl === null) {
            $this->initUrl();
        }

        return $this->currentUrl;
    }

    /**
     * Returns additional parsed params
     *
     * @return array
     */
    public function getAdditionalParams()
    {
        if ($this->currentUrl === null) {
            $this->initUrl();
        }

        return $this->additionalParams;
    }

    /**
     * Hook for processing path changes
     *
     * @param string $path
     * @return string
     */
    protected function processPath($path)
    {
        return $path;
    }
}
