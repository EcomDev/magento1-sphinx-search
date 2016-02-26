<?php

use EcomDev_Sphinx_Model_Sphinx_Facet_Filter_Condition_Multiple as MultipleCondition;
use EcomDev_Sphinx_Model_Sphinx_Facet_Filter_Condition_Option as OptionCondition;

class EcomDev_Sphinx_Model_Sphinx_Facet_Virtual_Option
    extends EcomDev_Sphinx_Model_Sphinx_AbstractFacet
{
    protected $_optionsLabel;

    protected $_optionsMap;

    /**
     * Configuration for an attribute
     *
     * @param string $filterName
     * @param string $label
     * @param string[] $options
     * @param bool $isMultiple
     */
    public function __construct($filterName, $label, array $options, $isMultiple = true)
    {
        $this->_optionsLabel = $options;

        foreach ($this->_optionsLabel as $optionId => $row) {
            $this->_optionsMap[$row['value']] = $optionId;
        }

        parent::__construct(
            $filterName,
            $filterName,
            $label
        );

        $this->_isSelfFilterable = !$isMultiple;
    }

    /**
     * @param string $value
     * @return array
     */
    protected function _processValue($value)
    {
        if (!$this->_isSelfFilterable) {
            $options = array_filter(
                array_map(
                    function ($item) {
                        $item = trim($item);
                        if (isset($this->_optionsMap[$item])) {
                            return $item;
                        }

                        return false;
                    },
                    explode(',', $value)
                )
            );

            if ($options) {
                return $options;
            }

        } elseif (is_string($value) && isset($this->_optionsMap[$value])) {
            return $value;
        }

        return null;
    }

    /**
     * Returns serializable data
     *
     * @return mixed[]
     */
    protected function _serializableData()
    {
        return parent::_serializableData() + [
            '_optionsLabel' => $this->_optionsLabel,
            '_optionsMap' => $this->_optionsMap
        ];
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
            $values = [];
            foreach ($this->_value as $value) {
                $values[] = $this->_optionsMap[$value];
            }

            $this->_filterCondition = new MultipleCondition($this, $values);
        } else {
            $this->_filterCondition = new OptionCondition($this, $this->_optionsMap[$this->_value]);
        }

        return $this;
    }

    /**
     * Should process sphinx response for current facet
     *
     * @param array $data
     * @return array
     */
    protected function _processSphinxResponse(array $data)
    {
        $result = [];

        foreach ($data as $row) {
            if (isset($this->_optionsLabel[$row['value']])) {
                $mapped = $this->_optionsLabel[$row['value']];
                $result[] = [
                    'value' => $mapped['value'],
                    'label' => $mapped['label'],
                    'count' => $row['count'],
                    'position' => $mapped['position']
                ];
            }
        }

        usort($result, function ($a, $b) {
            if (!isset($a['position']) || !isset($b['position'])
                || (int)$a['position'] == (int)$b['position']) {
                return 0;
            }

            return ((int)$a['position'] > (int)$b['position'] ? 1 : -1);
        });

        if (empty($result)) {
            return null;
        }

        return $result;
    }

}
