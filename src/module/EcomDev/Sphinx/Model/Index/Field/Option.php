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
     * Attribute code
     *
     * @var string
     */
    private $attributeCode;

    /**
     * Multi-value separator for text
     *
     * @var string
     */
    private $textSeparator;

    /**
     * @param string $type
     * @param string $name
     * @param string|null $attributeCode
     */
    public function __construct($type, $name, $attributeCode = null, $textSeparator = ' ')
    {
        if ($attributeCode === null) {
            $attributeCode = $name;
        }
        parent::__construct($type, $name);
        $this->attributeCode = $attributeCode;
        $this->textSeparator = $textSeparator;
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

        /** @var EcomDev_Sphinx_Model_Resource_Index_Reader_Plugin_Attribute_Eav_Option_Hash $options */
        $options = $row->getValue('_' . $this->attributeCode . '_label', false);

        if (!$options) {
            return '';
        }

        if (!is_array($value) && $this->isText()) {
            return (string)$options->getOption($value, $this->attributeCode);
        } elseif (!is_array($value)) {
            return ($options->getOption($value, $this->attributeCode) !== false ? $value : '');
        }

        $result = [];

        foreach ($value as $optionId) {
            $label = $options->getOption($optionId, $this->attributeCode);

            if ($label === false) {
                continue;
            }

            if ($this->isText()) {
                $result[] = $label;
            } else {
                $result[] = $optionId;
            }
        }

        if ($this->isText()) {
            // Just string output for a field
            return implode($this->textSeparator, $result);
        }

        return $result;
    }
}
