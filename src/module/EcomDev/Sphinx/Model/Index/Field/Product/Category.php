<?php

use EcomDev_Sphinx_Contract_DataRowInterface as DataRowInterface;
use EcomDev_Sphinx_Contract_Reader_ScopeInterface as ScopeInterface;

class EcomDev_Sphinx_Model_Index_Field_Product_Category
    extends EcomDev_Sphinx_Model_Index_AbstractField
{
    /**
     * Flag for direct category relation filter
     *
     * @var bool
     */
    private $isDirect;

    /**
     * Flag for category id retrieval
     *
     * @var bool
     */
    private $isId;


    /**
     * Sets field properties
     *
     * @param string $type
     * @param string $name
     * @param bool $isDirect
     * @param bool $isId
     */
    public function __construct($type, $name, $isDirect, $isId)
    {
        parent::__construct($type, $name);
        $this->isDirect = $isDirect;
        $this->isId = $isId;
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
        $categories = $row->getValue('_categories', []);

        $result = [];

        if (is_array($categories)) {
            foreach ($categories as $row) {
                if ($this->isDirect && empty($row['is_parent'])) {
                    continue;
                }

                if (!$this->isId && $this->isMultiple()) { // There cannot be MVA non id based
                    continue;
                } elseif ($this->isId && $this->isMultiple()) {
                    $result[] = $row['category_id'];
                } elseif ($this->isId && $this->isText()) {
                    $result[] = sprintf('cat_%s', $row['category_id']);
                } elseif ($this->isText()) {
                    $result[] = $row['name'];
                } elseif ($this->isInt() && !empty($row['is_parent']) && !empty($row['position'])) {
                    $result[] = abs((int)$row['position']);
                }
            }
        }


        if ($this->isMultiple()) {
            return $result;
        } elseif ($this->isText()) {
            return implode(' ', $result);
        } elseif ($this->isInt() && !empty($result)) {
            return min($result);
        }

        // Products without position should go in the end of the list
        return 9999999;
    }
}
