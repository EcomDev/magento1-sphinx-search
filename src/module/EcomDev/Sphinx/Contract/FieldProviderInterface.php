<?php

use EcomDev_Sphinx_Contract_FieldInterface as FieldInterface;
use EcomDev_Sphinx_Contract_ConfigurationInterface as ConfigurationInterface;

interface EcomDev_Sphinx_Contract_FieldProviderInterface
{
    /**
     * Returns fields based on internal logic
     *
     * @return FieldInterface[]
     */
    public function getFields();

    /**
     * Returns attribute codes by type
     *
     * @return string[][]
     */
    public function getAttributeCodeByType();
}
