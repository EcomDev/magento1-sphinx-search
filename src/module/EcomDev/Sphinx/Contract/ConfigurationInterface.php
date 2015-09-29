<?php

use EcomDev_Sphinx_Contract_FieldInterface as FieldInterface;
use EcomDev_Sphinx_Contract_FieldProviderInterface as FieldProviderInterface;

/**
 * Configuration provider
 *
 *
 */
interface EcomDev_Sphinx_Contract_ConfigurationInterface
{
    /**
     * Returns fields from field provider
     *
     * @return FieldInterface[]
     */
    public function getFields();

    /**
     * Adds field provider to configuration object
     *
     * @param FieldProviderInterface $provider
     * @return $this
     */
    public function addFieldProvider(FieldProviderInterface $provider);

    /**
     * Returns attribute code that are configured to be used
     *
     * @param string|null filters attribute code by type
     * @return string[]
     */
    public function getAttributeCodes($type = null);

    /**
     * Returns attributes grouped by code and type
     *
     * @return string[][]
     */
    public function getAttributeCodesGroupedByType();
}
