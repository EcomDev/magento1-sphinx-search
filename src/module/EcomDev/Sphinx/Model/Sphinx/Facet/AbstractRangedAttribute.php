<?php

use EcomDev_Sphinx_Model_Attribute as Attribute;
use EcomDev_Sphinx_Model_Sphinx_Query_Builder as QueryBuilder;
use EcomDev_Sphinx_Model_Source_Attribute_Filter_Type as FilterType;
use EcomDev_Sphinx_Model_Sphinx_Facet_Filter_Condition_Limit as LimitCondition;
use EcomDev_Sphinx_Model_Sphinx_Facet_Filter_Condition_Range as RangeCondition;

abstract class EcomDev_Sphinx_Model_Sphinx_Facet_AbstractRangedAttribute
    extends EcomDev_Sphinx_Model_Sphinx_Facet_AbstractAttribute
{
    /**
     * List of range values for attribute
     * 
     * @var int[]
     */
    protected $_ranges;

    /**
     * Range facets cannot be self filterable
     * 
     * @var bool
     */
    protected $_isSelfFilterable = true;

    /**
     * Is custom value allowed
     * 
     * @var bool
     */
    protected $_isCustomValueAllowed = false;

    /**
     * Type of a filter
     * 
     * @var string
     */
    protected $_filterType;

    /**
     * Backend type of attribute
     * 
     * @var string
     */
    protected $_backendType;
    
    /**
     * Configuration for an attribute
     *
     * @param Attribute $attribute
     * @param int       $rangeStep
     * @param int       $rangeCount
     */
    public function __construct(Attribute $attribute, $rangeStep, $rangeCount)
    {
        parent::__construct($attribute);
        
        $this->_filterType = $this->_attribute->getFilterType();
        $this->_isCustomValueAllowed = (bool)$this->_attribute->getIsCustomValueAllowed();
        $this->_backendType = $this->_attribute->getBackendType();
        $this->_renderType = self::RENDER_TYPE_RANGE;
        if ($this->_filterType === FilterType::TYPE_RANGE) {
            $this->_ranges = array();
            for ($i = 1; $i <= $rangeCount; ++$i) {
                $this->_ranges[] = (int)($rangeStep * $i);
            }
        } else {
            $this->_renderType = self::RENDER_TYPE_LIMIT;
            $this->_isSelfFilterable = false;
        }
    }
    
    /**
     * Processes filter value
     *
     * @param string $value
     * @return string[]|int[]
     */
    protected function _processValue($value)
    {
        if ($this->_filterType  === FilterType::TYPE_RANGE) {
            if ($this->_isCustomValueAllowed && strpos($value, '+') !== false) {
                return $value;
            }
            
            return array_filter(explode(',', $value), function ($item) {
                return $item !== '';
            });
        } elseif ($this->_filterType === FilterType::TYPE_SLIDER && strpos($value, '+') !== false) {
            return $value;
        }
        
        return null;
    }
    
    /**
     * Facet SphinxQL for retrieval of data
     *
     * @return QueryBuilder
     */
    public function getFacetSphinxQL(QueryBuilder $baseQuery)
    {
        $query = clone $baseQuery;

        if ($this->_filterType === FilterType::TYPE_RANGE) {
            $this->_rangeFacetSphinxQL($query);
        } elseif ($this->_filterType === FilterType::TYPE_SLIDER) {
            $this->_limitFacetSphinxQL($query);
        }

        return $query;
    }

    /**
     * Facet select configuration for range options 
     * 
     * @param EcomDev_Sphinx_Model_Sphinx_Query_Builder $query
     * @return $this
     */
    protected function _rangeFacetSphinxQL(QueryBuilder $query)
    {
        $query->select(
            $query->exprFormat('INTERVAL(%s,%s) as %s',
                $this->getColumnName(),
                implode(',', $this->_ranges),
                $query->quoteIdentifier('value')
            ),
            $query->exprFormat('COUNT(*) as %s', $query->quoteIdentifier('count'))
        );

        $query->from($this->_getIndexNames())
            ->groupBy('value')
            ->orderBy('value', 'asc')
            ->limit(count($this->_ranges)+1);
        return $this;
    }

    /**
     * Facet select configuration for limit condition
     *
     * @param EcomDev_Sphinx_Model_Sphinx_Query_Builder $query
     * @return $this
     */
    protected function _limitFacetSphinxQL(QueryBuilder $query)
    {
        $query->select(
            $query->exprFormat(
                'MIN(%s) as %s', 
                $query->quoteIdentifier($this->getColumnName()),
                'value_min'
            ),
            $query->exprFormat(
                'MAX(%s) as %s',
                $query->quoteIdentifier($this->getColumnName()),
                'value_max'
            )
        );

        $query->from($this->_getIndexNames())
            ->limit(1);
        
        return $this;
    }

    /**
     * Initializes filter condition
     * 
     * @return $this
     */
    protected function _initFilterCondition()
    {
        if (is_array($this->_value)) {
            $this->_filterCondition = new RangeCondition(
                $this, $this->_value, $this->_ranges
            );
        } elseif (is_string($this->_value) && strpos($this->_value, '+') !== false) {
            list($minValue, $maxValue) = explode('+', $this->_value);
            $this->_filterCondition = new LimitCondition(
                $this, $minValue, $maxValue, $this->_backendType
            );
        }
        
        return $this;
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
        foreach ($data as $row) {
            if (isset($row['value_min']) && isset($row['value_max'])) {
                $row['value'] = 'limit';
                $row['label'] = array($row['value_min'], $row['value_max']);
                if (((float)$row['value_max'] - (float)$row['value_min']) > 0.0001) {
                    $result[] = $row;
                }
                break;
            }
            
            if (isset($this->_ranges[$row['value']])) {
                $row['label'] = $this->_ranges[$row['value']];
                $result[] = $row;
            }
        }
        
        if (!$result) {
            return null;
        }
        
        return $result;
    }

    protected function _serializableData()
    {
        return parent::_serializableData() + array(
            '_ranges' => $this->_ranges, 
            '_filterType' => $this->_filterType,
            '_backendType' => $this->_backendType,
            '_isCustomValueAllowed' => $this->_isCustomValueAllowed
        );
    }


}
