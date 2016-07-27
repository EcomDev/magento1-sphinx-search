<?php

use EcomDev_Sphinx_Model_Field as VirtualField;

/**
 * Virtual field type provider for indexer and scope models
 *
 *
 */
interface EcomDev_Sphinx_Contract_Field_TypeInterface
{
    /**
     * @param EcomDev_Sphinx_Model_Field $field
     * @return EcomDev_Sphinx_Contract_FieldInterface
     */
    public function getIndexField(VirtualField $field);

    /**
     * Returns a facet
     *
     * @param EcomDev_Sphinx_Model_Field $field
     * @return EcomDev_Sphinx_Model_Sphinx_FacetInterface
     */
    public function getFacet(VirtualField $field);

    /**
     * Validates a virtual field
     *
     * @param EcomDev_Sphinx_Model_Field $field
     * @param string $mode
     * @return string[]|bool
     */
    public function validate(VirtualField $field, $mode);

    /**
     * Returns option hash array of virtual options
     *
     * @param VirtualField $field
     * @return string[]
     */
    public function getOptionHash(VirtualField $field);
}
