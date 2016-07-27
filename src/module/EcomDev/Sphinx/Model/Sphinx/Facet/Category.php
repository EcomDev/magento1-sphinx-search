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
    protected $_categoryData;

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
     * Currently selected category id
     *
     * @var string[]
     */
    protected $_currentCategoryData;

    /**
     * Configures basic facet data
     *
     * @param string $label
     * @param string[] $categoryData
     * @param string[] $excludeCategoryIds
     * @param string[] $currentCategoryData
     */
    public function __construct(
        $label,
        array $categoryData,
        array $excludeCategoryIds = array(),
        array $currentCategoryData = array(),
        $renderType = null
    )
    {
        parent::__construct('anchor_category_ids', 'cat', $label);

        $this->_categoryData = $categoryData;
        $this->_excludeCategoryIds = $excludeCategoryIds;
        $this->_currentCategoryData = $currentCategoryData;

        if ($renderType !== null) {
            $this->_renderType = $renderType;
        }
    }


    /**
     * Returns a list of array data
     *
     * @return string[][]
     */
    public function getCategoryData()
    {
        return $this->_categoryData;
    }

    /**
     * Returns current category id
     *
     * @return int|null
     */
    public function getCurrentCategoryData()
    {
        return $this->_currentCategoryData;
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
            && isset($this->_categoryData[$item])
            && !in_array($item, $this->_excludeCategoryIds);
        });
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

        $categoryOrder = array_keys($this->_categoryData);

        foreach ($data as $row) {
            if (isset($this->_categoryData[$row['value']])
                && !in_array($row['value'], $this->_excludeCategoryIds)) {
                $row['label'] = $this->_categoryData[$row['value']]['name'];
                $result[array_search($row['value'], $categoryOrder)] = $row;

                if (isset($row['count'])) {
                    $this->_categoryData[$row['value']]['count'] = $row['count'];
                } else {
                    $this->_categoryData[$row['value']]['count'] = 0;
                }
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
        + array('_categoryData' => $this->_categoryData,
            '_excludeCategoryIds' => $this->_excludeCategoryIds);
    }

}
