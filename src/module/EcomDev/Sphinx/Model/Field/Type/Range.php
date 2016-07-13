<?php

use EcomDev_Sphinx_Model_Field as VirtualField;
use EcomDev_Sphinx_Model_Index_Field_Map_Range as RangeField;
use EcomDev_Sphinx_Model_Sphinx_Facet_Virtual_Option as OptionFacet;

class EcomDev_Sphinx_Model_Field_Type_Range
    extends EcomDev_Sphinx_Model_Field_Type_AbstractType
{
    public function getIndexField(VirtualField $field)
    {
        $mappingConfig = $field->getConfigurationValue('field/map');
        if (!is_array($mappingConfig)) {
            $mappingConfig = [];
        }

        uasort($mappingConfig, $this->cmpPositionClosure());

        $map = [
            'sorted_ranges' => []
        ];

        $index = 1;

        foreach ($mappingConfig as $uniqueCode => $target) {
            $map['sorted_ranges'][$index] = [];
            if (isset($target['from']) && $target['from'] !== '') {
                $map['sorted_ranges'][$index]['from'] = $target['from'];
            }

            if (isset($target['to']) && $target['to'] !== '') {
                $map['sorted_ranges'][$index]['to'] = $target['to'];
            }

            if (empty($map['sorted_ranges'][$index])) {
                unset($map['sorted_ranges'][$index]);
            }

            $index ++;
        }

        return new RangeField(
            $field->getCode(), $field->getConfigurationValue('related_attribute'), $map
        );
    }

    /**
     * Returns facet for range field
     *
     * @param EcomDev_Sphinx_Model_Field $field
     * @return EcomDEv_Sphinx_Model_Sphinx_Facet_Virtual_Option
     */
    public function getFacet(VirtualField $field)
    {
        $options = [];
        $mappingConfig = $field->getConfigurationValue('field/map');
        if (!is_array($mappingConfig)) {
            $mappingConfig = [];
        }

        $index = 1;

        foreach ($mappingConfig as $uniqueCode => $target) {
            $options[$index] = [
                'label' => $this->getStoreLabel($target['store_label'], $target['label']),
                'position' => isset($target['position']) ? $target['position'] : null,
                'value' => $uniqueCode
            ];

            $index ++;
        }

        return new OptionFacet(
            $field->getCode(),
            $this->getStoreLabel($field->getConfigurationValue('store_name'), $field->getName()),
            $options
        );
    }

    /**
     * Returns option hash array of virtual options
     *
     * @param VirtualField $field
     * @return string[]
     */
    public function getOptionHash(VirtualField $field)
    {
        $mappingConfig = $field->getConfigurationValue('field/map');
        if (!is_array($mappingConfig)) {
            $mappingConfig = [];
        }

        $result = [];

        foreach ($mappingConfig as $uniqueCode => $target) {
            $result[$uniqueCode] = $target['label'];
        }

        return $result;
    }
}
