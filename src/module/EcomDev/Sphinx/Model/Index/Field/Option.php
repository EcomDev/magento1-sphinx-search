<?php

use EcomDev_Sphinx_Contract_DataRowInterface as DataRowInterface;
use EcomDev_Sphinx_Contract_Reader_ScopeInterface as ScopeInterface;

/**
 * Option field type
 */
class EcomDev_Sphinx_Model_Index_Field_Option
    extends EcomDev_Sphinx_Model_Index_AbstractField
{
    /**
     * List of options per store view
     *
     * @var string[][]
     */
    private $options;

    /**
     * Attribute code
     *
     * @var string
     */
    private $attributeCode;

    /**
     * @param string $type
     * @param string $name
     * @param string[][] $options
     * @param string|null $attributeCode
     */
    public function __construct($type, $name, $options, $attributeCode = null)
    {
        if ($attributeCode === null) {
            $attributeCode = $name;
        }
        parent::__construct($type, $name);
        $this->options = $options;
        $this->attributeCode = $attributeCode;
    }

    /**
     * Returns data for sphinx
     *
     * @param DataRowInterface $row
     * @param ScopeInterface $scope
     * @return string[]|string
     */
    public function getValue(DataRowInterface $row, ScopeInterface $scope)
    {
        $value = $row->getValue($this->attributeCode, []);

        if (!is_array($value)) {
            $value = (array)$value;
        }

        $result = [];

        $storeId = 0;
        if ($scope->hasFilter('store_id')) {
            $storeId = $scope->getFilter('store_id')->getValue();
        }

        foreach ($value as $optionId) {
            if (!isset($this->options[$optionId])) {
                continue;
            }

            if ($this->getType() === self::TYPE_FIELD && isset($this->options[$optionId][0])) {
                $result[] = (
                    isset($this->options[$optionId][$storeId]) ?
                    $this->options[$optionId][$storeId] :
                    $this->options[$optionId][0]
                );
            } elseif ($this->getType() !== self::TYPE_FIELD) {
                $result[] = $optionId;
            }
        }

        if ($this->getType() === self::TYPE_FIELD) {
            // Just string output for a field
            return implode(' ', $result);
        }

        return $result;
    }


}
