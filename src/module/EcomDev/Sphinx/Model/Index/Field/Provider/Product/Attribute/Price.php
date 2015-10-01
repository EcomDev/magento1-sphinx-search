<?php

use EcomDev_Sphinx_Model_Config as SphinxConfig;
use EcomDev_Sphinx_Model_Index_Field_Price as PriceField;

class EcomDev_Sphinx_Model_Index_Field_Provider_Product_Attribute_Price
    extends EcomDev_Sphinx_Model_Index_Field_AbstractProvider
{
    /**
     * @var SphinxConfig
     */
    private $sphinxConfig;

    /**
     * Receives sphinx config as argument
     *
     * @param SphinxConfig $sphinxConfig
     */
    public function __construct(SphinxConfig $sphinxConfig)
    {
        $this->sphinxConfig = $sphinxConfig;
    }

    /**
     * Returns fields based on internal logic
     *
     * @return \EcomDev_Sphinx_Contract_FieldInterface[]
     */
    public function getFields()
    {
        $container = new stdClass();
        $container->fields = [];

        $groupedFieldNames = $this->sphinxConfig->getPriceColumns(true);

        foreach ($groupedFieldNames as $customerGroupId => $fields) {
            foreach ($fields as $fieldName => $column) {
                $container->fields[] = new PriceField($fieldName, $column, $customerGroupId);
            }
        }

        Mage::dispatchEvent('ecomdev_sphinx_provider_product_price_fields', ['container' => $container]);
        return $container->fields;
    }
}
