<?php

use EcomDev_Sphinx_Model_Sphinx_Query_Builder as QueryBuilder;
use EcomDev_Sphinx_Model_Sphinx_Facet_Filter_Condition_Multiple as MultipleCondition;

class EcomDev_Sphinx_Model_Sphinx_Facet_Category
    extends EcomDev_Sphinx_Model_Sphinx_AbstractFacet
{
    /**
     * List of category names to be used for label
     * 
     * @var string[]
     */
    protected $_categoryNames;

    /**
     * Excluded category ids
     * 
     * @var array[]
     */
    protected $_excludeCategoryIds;

    /**
     * Category cannot be self filterable
     * 
     * @var bool
     */
    protected $_isSelfFilterable = false;
    
    /**
     * Configures basic facet data
     *
     * @param string $label
     * @param string[] $categoryNames
     * @param string[] $excludeCategoryIds
     */
    public function __construct($label, array $categoryNames, array $excludeCategoryIds = array())
    {
        parent::__construct('anchor_category_ids', 'cat', $label);
        $this->_categoryNames = $categoryNames;
        $this->_excludeCategoryIds = $excludeCategoryIds;
    }
        
    
    /**
     * Initializes filter condition
     *
     * @return $this
     */
    protected function _initFilterCondition()
    {
        if (is_array($this->_value)) {
            $this->_filterCondition = new MultipleCondition($this, $this->_value);
        } else {
            $this->_filterCondition = false;
            $this->_value = null;
        }
        
        return $this;
    }

    /**
     * Processes filter value
     *
     * @param string $value
     * @return int[]
     */
    protected function _processValue($value)
    {
        return array_filter(explode(',', $value), function ($item) {
            return $item !== '' 
                && isset($this->_categoryNames[$item]) 
                && !in_array($item, $this->_excludeCategoryIds);
        });
    }
    
    /**
     * Facet SphinxQL for retrieval of data
     *
     * @return \EcomDev_Sphinx_Model_Sphinx_Query_Builder
     */
    public function getFacetSphinxQL(QueryBuilder $baseQuery)
    {
        $query = clone $baseQuery;
        $query->select(
            $query->exprFormat('GROUPBY() as %s', $query->quoteIdentifier('value')),
            $query->exprFormat('COUNT(*) as %s', $query->quoteIdentifier('count'))
        );

        $query->from($this->_getIndexNames())
            ->groupBy($this->getColumnName())
            ->orderBy('count', 'desc')
            ->limit(200);
        return $query;
    }

    /**
     * Filters sphinx response to match available ranges
     *
     * @param array $data
     * @return array|null
     */
    protected function _processSphinxResponse(array $data)
    {
        $result = array();
        
        $categoryOrder = array_keys($this->_categoryNames);
        
        foreach ($data as $row) {
            if (isset($this->_categoryNames[$row['value']]) 
                && !in_array($row['value'], $this->_excludeCategoryIds)) {
                $row['label'] = $this->_categoryNames[$row['value']];
                $result[array_search($row['value'], $categoryOrder)] = $row;
            }
        }
        
        ksort($result);
        return $result;
    }

    /**
     * @return bool
     */
    public function isVisible()
    {
        return count($this->getOptions()) > 0;
    }

    protected function _serializableData()
    {
        return parent::_serializableData() 
            + array('_categoryNames' => $this->_categoryNames, 
                    '_excludeCategoryIds' => $this->_excludeCategoryIds);
    }

}
