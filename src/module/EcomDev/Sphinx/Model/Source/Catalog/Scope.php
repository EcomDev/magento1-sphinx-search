<?php

/**
 * Source model for a category attribute
 */
class EcomDev_Sphinx_Model_Source_Catalog_Scope
    extends Mage_Eav_Model_Entity_Attribute_Source_Abstract
{
    /**
     * Retrieve All options
     *
     * @param bool $multiple
     * @return array
     */
    public function getAllOptions()
    {
        return Mage::getSingleton('ecomdev_sphinx/source_scope')
            ->toOptionArray();
    }

}
