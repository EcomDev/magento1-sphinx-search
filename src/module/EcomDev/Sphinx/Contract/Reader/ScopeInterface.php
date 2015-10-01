<?php

use EcomDev_Sphinx_Contract_Reader_FilterInterface as FilterInterface;
use EcomDev_Sphinx_Contract_ConfigurationInterface as ConfigurationInterface;

interface EcomDev_Sphinx_Contract_Reader_ScopeInterface
{
    /**
     * Returns all filters that are assigned to scope
     *
     * @return FilterInterface[]
     */
    public function getFilters();

    /**
     * Returns true if filter is set for scope
     *
     * @param string $field
     * @return bool
     */
    public function hasFilter($field);

    /**
     * Returns filter instance if
     *
     * @param string $field
     * @param bool $multiple
     * @return FilterInterface[]|FilterInterface|bool
     */
    public function getFilter($field, $multiple = false);

    /**
     * Replaces existing filter instance
     *
     * @param FilterInterface $filter
     * @return $this
     */
    public function replaceFilter(FilterInterface $filter);

    /**
     * Returns configuration instance
     *
     * @return ConfigurationInterface
     */
    public function getConfiguration();
}
