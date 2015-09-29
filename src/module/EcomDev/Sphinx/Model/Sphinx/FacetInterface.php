<?php


use EcomDev_Sphinx_Model_Sphinx_Facet_Filter_ConditionInterface as FilterConditionInterface;
use EcomDev_Sphinx_Model_Sphinx_Facet_Filter_OptionInterface as OptionInterface;
use EcomDev_Sphinx_Model_Sphinx_Query_Builder as QueryBuilder;
/**
 * Facet filter interface
 * 
 */
interface EcomDev_Sphinx_Model_Sphinx_FacetInterface
{
    /**
     * Option renderer type
     */
    const RENDER_TYPE_OPTION = 'option';

    /**
     * Link renderer type
     */
    const RENDER_TYPE_LINK = 'link';

    /**
     * Range renderer type
     *
     */
    const RENDER_TYPE_RANGE = 'range';

    /**
     * Renderer type limit
     *
     */
    const RENDER_TYPE_LIMIT = 'limit';

    /**
     * Returns column name for a filter
     * 
     * @return string
     */
    public function getColumnName();

    /**
     * Returns filter value object
     * 
     * @return FilterConditionInterface|bool
     */
    public function getFilterCondition();

    /**
     * Facet SphinxQL for retrieval of data
     * 
     * @return QueryBuilder
     */
    public function getFacetSphinxQL(QueryBuilder $baseQuery);

    /**
     * This string returns a filter field in request
     * 
     * @return string
     */
    public function getFilterField();

    /**
     * This string returns a filter label
     *
     * @return string
     */
    public function getLabel();

    /**
     * Returns position of facet
     *
     * @return int
     */
    public function getPosition();

    /**
     * Apply filter parameter from request
     *
     * @param string|string[] $value
     * @return $this
     */
    public function apply($value);

    /**
     * This is used to notify facet about sphinx result set
     * 
     * @param array $data
     * @return mixed
     */
    public function setSphinxResponse(array $data);

    /**
     * Returns available options
     *
     * @return OptionInterface[]
     */
    public function getOptions();

    /**
     * Returns true if current option is active in facet
     * 
     * @param EcomDev_Sphinx_Model_Sphinx_Facet_Filter_OptionInterface $option
     * @return bool
     */
    public function isOptionActive(OptionInterface $option);

    /**
     * Returns a filter value for an option or whole facet
     * 
     * @param OptionInterface|null $option
     * @return string
     */
    public function getFilterValue(OptionInterface $option = null);
    
    /**
     * Returns sphinx is visible
     * 
     * @return bool
     */
    public function isVisible();

    /**
     * Indicates if facet is self filterable
     * 
     * @return bool
     */
    public function isSelfFilterable();

    /**
     * Tests if facet is available based on index information
     * 
     * @return bool
     */
    public function isAvailable();

    /**
     * Returns a renderer type for a facet
     *
     * @return string
     */
    public function getRenderType();
}
