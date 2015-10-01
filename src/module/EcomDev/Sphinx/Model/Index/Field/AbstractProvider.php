<?php

use EcomDev_Sphinx_Contract_ConfigurationInterface as ConfigurationInterface;

/**
 * Abstract field provider implementation
 *
 */
abstract class EcomDev_Sphinx_Model_Index_Field_AbstractProvider
    implements EcomDev_Sphinx_Contract_FieldProviderInterface
{
    /**
     * Returns attribute codes by type
     *
     * @return string[][]
     */
    public function getAttributeCodeByType()
    {
        return [];
    }

}

