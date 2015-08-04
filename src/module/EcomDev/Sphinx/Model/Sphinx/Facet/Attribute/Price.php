<?php

use EcomDev_Sphinx_Model_Attribute as Attribute;
use EcomDev_Sphinx_Model_Sphinx_Facet_Filter_OptionInterface as OptionInterface;

class EcomDev_Sphinx_Model_Sphinx_Facet_Attribute_Price
    extends EcomDev_Sphinx_Model_Sphinx_Facet_AbstractRangedAttribute
{
    /**
     * Customer group id 
     * 
     * @var int
     */
    protected $_customerGroupId;

    /**
     * Currency model instance
     *
     * @var Mage_Directory_Model_Currency
     */
    protected $_currency;
    
    public function __construct(Attribute $attribute, $rangeStep, $rangeCount, $customerGroupId = 0)
    {
        parent::__construct($attribute, $rangeStep, $rangeCount);
        $this->_customerGroupId = $customerGroupId;
        $this->_columnName = sprintf('price_index_min_price_%d', $this->_customerGroupId);
    }

    /**
     * Should return back a label value for facet
     *
     * @param string[] $row
     * @param bool $isFirst
     * @param bool $isLast
     * @return string
     */
    protected function _prepareOptionLabel($row)
    {
        if (is_array($row['label'])) {
            return parent::_prepareOptionLabel($row);
        }

        $index = $row['value'];

        if ($index == 0) {
            $value = $this->_getCurrency()->format($this->_ranges[$index]);
            return Mage::helper('ecomdev_sphinx')->__('Below %s', $value);
        } elseif (!isset($this->_ranges[$row['value']])) {
            $value = $this->_getCurrency()->format($row['label']);
            return Mage::helper('ecomdev_sphinx')->__('Above %s', $value);
        }

        $minValue = $this->_getCurrency()->format($this->_ranges[$index - 1]);
        $maxValue = $this->_getCurrency()->format($this->_ranges[$index]);

        return Mage::helper('ecomdev_sphinx')->__('%s - %s', $minValue, $maxValue);
    }


    protected function _getCurrency()
    {
        if ($this->_currency === null) {
            $this->_currency = Mage::app()->getStore()->getCurrentCurrency();
        }

        return $this->_currency;
    }
}
