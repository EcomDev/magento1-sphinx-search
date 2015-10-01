<?php

use EcomDev_Sphinx_Contract_DataRowInterface as DataRowInterface;
use EcomDev_Sphinx_Contract_Reader_ScopeInterface as ScopeInterface;

/**
 * Price field
 */
class EcomDev_Sphinx_Model_Index_Field_Price
    extends EcomDev_Sphinx_Model_Index_AbstractField
{
    /**
     * Customer group identifier
     *
     * @var int
     */
    private $customerGroupIdentifier;

    /**
     * Name of index field
     *
     * @var string
     */
    private $indexField;

    /**
     * Sets field properties
     *
     * @param string $name
     * @param string $indexField
     * @param string $customerGroupIdentifier
     */
    public function __construct($name, $indexField, $customerGroupIdentifier)
    {
        parent::__construct(self::TYPE_ATTRIBUTE_FLOAT, $name);
        $this->indexField = $indexField;
        $this->customerGroupIdentifier = $customerGroupIdentifier;
    }


    /**
     * Returns data for sphinx
     *
     * @param DataRowInterface $row
     * @param ScopeInterface $scope
     * @return string
     */
    public function getValue(DataRowInterface $row, ScopeInterface $scope)
    {
        $index = $row->getValue('price_index', []);

        if (isset($index[$this->customerGroupIdentifier]) && $index[$this->indexField] !== null) {
            return $index[$this->customerGroupIdentifier][$this->indexField];
        }

        return '';
    }
}
