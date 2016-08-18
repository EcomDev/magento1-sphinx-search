<?php

use EcomDev_Sphinx_Model_Field as VirtualField;
use EcomDev_Sphinx_Model_Index_Field_Map_Group as GroupField;
use EcomDev_Sphinx_Model_Sphinx_Facet_Virtual_Option as OptionFacet;

class EcomDev_Sphinx_Model_Field_Type_Grouped
    extends EcomDev_Sphinx_Model_Field_Type_AbstractType
{
    public function getIndexField(VirtualField $field)
    {
        $mappingConfig = $field->getConfigurationValue('field/map');
        if (!is_array($mappingConfig)) {
            $mappingConfig = [];
        }

        $map = [
            'reverse_map' => []
        ];

        $index = 1;

        foreach ($mappingConfig as $uniqueCode => $target) {
            foreach ($target['target'] as $optionId) {
                if (empty($optionId)) {
                    continue;
                }

                if (!isset($map['reverse_map'][$optionId])) {
                    $map['reverse_map'][$optionId] = $index;
                    continue;
                }

                if (!is_array($map['reverse_map'][$optionId])) {
                    $map['reverse_map'][$optionId] = [$map['reverse_map'][$optionId]];
                }

                $map['reverse_map'][$optionId][] = $index;
            }
            $index ++;
        }

        return new GroupField(
            $field->getCode(), $field->getConfigurationValue('related_attribute'), $map
        );
    }

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
