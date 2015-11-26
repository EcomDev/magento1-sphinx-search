<?php

use EcomDev_Sphinx_Model_Field as VirtualField;

abstract class EcomDev_Sphinx_Model_Field_Type_AbstractType
    implements EcomDev_Sphinx_Contract_Field_TypeInterface
{
    /**
     * @param EcomDev_Sphinx_Model_Field $field
     * @param string $mode
     * @return bool
     */
    public function validate(VirtualField $field, $mode)
    {
        return true;
    }

    protected function cmpPositionClosure()
    {
        return function ($a, $b) {
            if (!isset($a['position']) || !isset($b['position'])
                || (int)$a['position'] == (int)$b['position']) {
                return 0;
            }

            return ((int)$a['position'] > (int)$b['position'] ? 1 : -1);
        };
    }

    /**
     * Return store based label
     *
     * @param string[] $storeLabels
     * @param string $defaultLabel
     * @return string
     */
    protected function getStoreLabel($storeLabels, $defaultLabel)
    {
        $storeCode = Mage::app()->getStore()->getCode();
        if (is_array($storeLabels)
            && !empty($storeLabels[$storeCode])
            && trim($storeLabels[$storeCode]) !== '') {
            return trim($storeLabels[$storeCode]);
        }

        return $defaultLabel;
    }
}
