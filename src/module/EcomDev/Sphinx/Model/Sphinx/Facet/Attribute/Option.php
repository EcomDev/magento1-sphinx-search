<?php

use EcomDev_Sphinx_Model_Attribute as Attribute;
use EcomDev_Sphinx_Model_Source_Attribute_Filter_Type as FilterType;
use EcomDev_Sphinx_Model_Sphinx_Facet_Filter_Condition_Multiple as MultipleCondition;
use EcomDev_Sphinx_Model_Sphinx_Facet_Filter_Condition_Option as OptionCondition;

/**
 * Attribute option based facet
 */
class EcomDev_Sphinx_Model_Sphinx_Facet_Attribute_Option
    extends EcomDev_Sphinx_Model_Sphinx_Facet_AbstractAttribute
    implements EcomDev_Sphinx_Model_Sphinx_Facet_OptionAwareInterface
{
    /**
     * Indicates that attribute is self filterable
     * 
     * @param EcomDev_Sphinx_Model_Attribute $attribute
     * @param null|string $filterName
     * @param null|string $columnName
     * @param null|string $label
     */
    public function __construct(Attribute $attribute, $filterName = null, $columnName = null, $label = null)
    {
        parent::__construct($attribute, $filterName, $columnName, $label);
        $this->_isSelfFilterable = $this->_attribute->getFilterType() !== FilterType::TYPE_MULTIPLE;
    }

    /**
     * Initializes filter condition
     *
     * @return $this
     */
    protected function _initFilterCondition()
    {
        if ($this->_value === null) {
            $this->_filterCondition = false;
            return $this; 
        }
        
        if (!$this->_isSelfFilterable) {
            $this->_filterCondition = new MultipleCondition($this, $this->_value);
        } else {
            $this->_filterCondition = new OptionCondition($this, $this->_value);
        }
        
        return $this;
    }

    /**
     * Processes filter value
     *
     * @param string $value
     * @return string[]|int[]
     */
    protected function _processValue($value)
    {
        if (!$this->_isSelfFilterable) {
            return array_filter(
                array_map(
                    function ($item) {
                        return (int)$item;
                    },
                    explode(',', $value)
                )
            );
        } elseif ((int)$value) {
            return (int)$value;
        }
        
        return null;
    }

    /**
     * Returns all available option ids
     *
     * @return int[]
     */
    public function getOptionIds()
    {
        if ($this->_sphinxResponse === null) {
            return array();
        }
        
        $result = array();
        
        foreach ($this->_sphinxResponse as $row) {
            $result[] = (int)$row['value'];
        }
        
        return $result;
    }

    /**
     * Sets list of option labels by identifier
     *
     * @param string[] $optionLabel
     * @param string[] $sortOrder
     * @return $this
     */
    public function setOptionLabel(array $optionLabel, array $sortOrder)
    {
        if ($this->_sphinxResponse === null) {
            return $this;
        }

        $result = array();
        foreach ($this->_sphinxResponse as $row) {
            if (isset($optionLabel[$row['value']])) {
                $row['label'] = $optionLabel[$row['value']];
                if (isset($sortOrder[$row['value']])) {
                    $result[$sortOrder[$row['value']]] = $row;    
                } else {
                    $result[] = $row;
                }
            }
        }
        
        if (empty($result)) {
            $this->_sphinxResponse = null;
        } else {
            ksort($result);
            $this->_sphinxResponse = $result;
        }
        
        return $this;
    }
}
