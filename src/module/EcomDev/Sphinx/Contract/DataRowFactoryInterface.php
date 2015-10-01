<?php

use EcomDev_Sphinx_Contract_DataRowInterface as DataRowInterface;

/**
 * Data Row Factory Interface
 *
 */
interface EcomDev_Sphinx_Contract_DataRowFactoryInterface
{
    /**
     * Return a new data row instance
     *
     * @param array $main
     * @param array $additional
     * @return DataRowInterface
     */
    public function createDataRow(array $main, array $additional);
}
