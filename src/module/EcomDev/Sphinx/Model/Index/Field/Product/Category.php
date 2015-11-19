<?php

use EcomDev_Sphinx_Contract_DataRowInterface as DataRowInterface;
use EcomDev_Sphinx_Contract_Reader_ScopeInterface as ScopeInterface;

class EcomDev_Sphinx_Model_Index_Field_Product_Category
    extends EcomDev_Sphinx_Model_Index_AbstractField
{
    /**
     * Flag for direct category relation filter
     *
     * @var string
     */
    private $field;

    /**
     * @var bool
     */
    private $key;


    /**
     * Sets field properties
     *
     * @param string $type
     * @param string $name
     * @param string $field
     * @param bool $key
     */
    public function __construct($type, $name, $field, $key = false)
    {
        parent::__construct($type, $name);
        $this->field = $field;
        $this->key = $key;
    }

    /**
     * Returns data from category entities in sphinx
     *
     * @param DataRowInterface $row
     * @param ScopeInterface $scope
     * @return string[]|string
     */
    public function getValue(DataRowInterface $row, ScopeInterface $scope)
    {
        $categories = $row->getValue($this->field, []);

        if (!is_array($categories)) {
            $categories = [];
        }


        if ($this->key) {
            $result = array_keys($categories);
        } else {
            $result = array_values($categories);
        }

        if ($this->isMultiple()) {
            return $result;
        } elseif ($this->isText()) {
            return implode(' ', $result);
        } elseif ($this->isInt() && $result) {
            return min($result);
        }

        // Default value for products without a position
        return 9999999;
    }
}
