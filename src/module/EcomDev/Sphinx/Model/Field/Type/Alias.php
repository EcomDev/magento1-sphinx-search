<?php

use EcomDev_Sphinx_Model_Field as VirtualField;
use EcomDev_Sphinx_Model_Index_Field_Map_Alias as AliasField;
use EcomDev_Sphinx_Model_Sphinx_Facet_Virtual_Alias as AliasFacet;

class EcomDev_Sphinx_Model_Field_Type_Alias
    extends EcomDev_Sphinx_Model_Field_Type_AbstractType
{
    public function getIndexField(VirtualField $field)
    {
        $mappingConfig = $field->getConfigurationValue('field/map');
        if (!is_array($mappingConfig)) {
            $mappingConfig = [];
        }

        $map = [
            'option_alias' => []
        ];
        foreach ($mappingConfig as $sourceValue => $target) {
            $map['option_alias'][$sourceValue] = $target['target'];
        }

        return new AliasField(
            $field->getCode(), $field->getConfigurationValue('related_attribute'), $map
        );
    }

    /**
     * Returns facet
     *
     * @param EcomDev_Sphinx_Model_Field $field
     * @return EcomDev_Sphinx_Model_Sphinx_Facet_Virtual_Alias
     */
    public function getFacet(VirtualField $field)
    {
        return new AliasFacet(
            $field->getRelatedAttribute(),
            $field->getCode(),
            $this->getStoreLabel($field->getConfigurationValue('store_name'), $field->getName())
        );
    }
}
