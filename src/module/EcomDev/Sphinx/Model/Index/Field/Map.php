<?php

use EcomDev_Sphinx_Contract_DataRowInterface as DataRowInterface;
use EcomDev_Sphinx_Contract_Reader_ScopeInterface as ScopeInterface;

class EcomDev_Sphinx_Model_Index_Field_Map
    extends EcomDev_Sphinx_Model_Index_AbstractField
{
    /**
     * Maps of the values
     *
     * @var string[][]
     */
    private $map;

    /**
     * EcomDev_Sphinx_Model_Index_Field_Map constructor.
     * @param string[][]|string[] $map
     * @param string $type
     * @param string $name
     */
    public function __construct($type, $name, array $map)
    {
        parent::__construct($type, $name);
        $this->map = $this->prepareMap($map);
    }

    /**
     * Prepares map for a value retriever
     *
     * @param string[][]|string[] $map
     * @return array
     */
    private function prepareMap(array $map)
    {
        $firstItem = current($map);
        if (!is_array($firstItem)) {
            $newMap = [];
            foreach ($map as $value => $mappedValue) {
                $newMap[$value] = [$this->getName() => $mappedValue];
            }

            return $newMap;
        }

        return $map;
    }

    /**
     * Returns data for sphinx
     *
     * @param DataRowInterface $row
     * @param ScopeInterface $scope
     * @return string[]
     */
    public function getValue(DataRowInterface $row, ScopeInterface $scope)
    {
        $value = [];

        foreach ($this->map as $targetValue => $conditions) {
            if ($this->checkCondition($row, $conditions)) {
                $value[] = $targetValue;
            }
        }

        return $value;
    }

    /**
     * Checks conditions of the row for particular value
     *
     * @param DataRowInterface $row
     * @param array $conditions
     * @return bool
     */
    protected function checkCondition(DataRowInterface $row, array $conditions)
    {
        foreach ($conditions as $field => $mapValue) {
            $value = $row->getValue($field);
            if (is_array($mapValue) && is_array($value)
                && array_intersect($mapValue, $value) !== $mapValue) {
                return false;
            } elseif (is_array($mapValue) && !in_array($value, $mapValue)) {
                return false;
            } elseif (is_array($value) && !in_array($mapValue, $value)) {
                return false;
            } elseif ($value != $mapValue) {
                return false;
            }
        }

        return true;
    }
}
