<?php

/**
 * Filter interface
 *
 */
interface EcomDev_Sphinx_Contract_Reader_FilterInterface
{
    /**
     * Returns filter field
     *
     * @return string
     */
    public function getField();

    /**
     * Returns value for which this filter is applied
     *
     * @return mixed|mixed[]
     */
    public function getValue();

    /**
     * @param string $tableAlias
     * @param Varien_Db_Select $select
     * @return $this
     */
    public function render($tableAlias, Varien_Db_Select $select);
}
