<?php

use EcomDev_Sphinx_Contract_DataRowInterface as DataRowInterface;
use EcomDev_Sphinx_Contract_Reader_ScopeInterface as ScopeInterface;

class EcomDev_Sphinx_Model_Index_Field
    extends EcomDev_Sphinx_Model_Index_AbstractField
{
    /**
     * Default field value
     *
     * @var string|null
     */
    private $default;

    /**
     * Source of the field in row
     *
     * @var string
     */
    private $source;

    /**
     * @param string $type
     * @param string $name
     * @param string|null $default
     * @param string|null $source
     */
    public function __construct($type, $name, $default = null, $source = null)
    {
        parent::__construct($type, $name);

        if ($source === null) {
            $source = $name;
        }

        $this->default = $default;
        $this->source = $source;
    }


    /**
     * Returns data for sphinx
     *
     * @param DataRowInterface $row
     * @param ScopeInterface $scope
     * @return string[]|string|null
     */
    public function getValue(DataRowInterface $row, ScopeInterface $scope)
    {
        $value = $row->getValue($this->source, $this->default);

        if ($this->isTimestamp() && $value) {
            $value = strtotime($value);
        }

        return $value;
    }
}
