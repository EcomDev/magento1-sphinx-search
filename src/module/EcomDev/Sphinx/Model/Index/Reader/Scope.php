<?php

use EcomDev_Sphinx_Contract_ConfigurationInterface as ConfigurationInterface;
use EcomDev_Sphinx_Contract_Reader_FilterInterface as FilterInterface;

class EcomDev_Sphinx_Model_Index_Reader_Scope
    implements EcomDev_Sphinx_Contract_Reader_ScopeInterface
{
    /**
     * Filters
     *
     * @var FilterInterface[][]
     */
    private $filters;

    /**
     * Configuration scope
     *
     * @var ConfigurationInterface
     */
    private $configuration;

    /**
     * EcomDev_Sphinx_Model_Index_Reader_Scope constructor.
     * @param FilterInterface[] $filters
     * @param ConfigurationInterface $configuration
     */
    public function __construct(array $filters, ConfigurationInterface $configuration)
    {
        $this->filters = [];
        foreach ($filters as $filter) {
            $this->filters[$filter->getField()][] = $filter;
        }

        $this->configuration = $configuration;
    }


    /**
     * Returns all filters that are assigned to scope
     *
     * @return FilterInterface[]
     */
    public function getFilters()
    {
        $result = [];

        foreach ($this->filters as $filters) {
            $result = array_merge($result, $filters);
        }

        return $result;
    }

    /**
     * Returns true if filter is set for scope
     *
     * @param string $field
     * @return bool
     */
    public function hasFilter($field)
    {
        return isset($this->filters[$field]);
    }

    /**
     * Returns filter instance if
     *
     * @param string $field
     * @param bool $multiple
     * @return bool|FilterInterface|FilterInterface[]
     */
    public function getFilter($field, $multiple = false)
    {
        if ($this->hasFilter($field)) {
            $filters = $this->filters[$field];
            if ($multiple) {
                return $filters;
            }

            return current($filters);
        }

        if ($multiple) {
            return [];
        }

        return false;
    }

    /**
     * Replaces existing filter instance
     *
     * @param FilterInterface $filter
     * @return $this
     */
    public function replaceFilter(FilterInterface $filter)
    {
        $this->filters[$filter->getField()] = [$filter];
        return $this;
    }


    /**
     * Returns configuration instance
     *
     * @return ConfigurationInterface
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

}
