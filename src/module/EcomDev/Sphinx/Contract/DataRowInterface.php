<?php

interface EcomDev_Sphinx_Contract_DataRowInterface
{
    /**
     * Sets identifier of a row
     *
     * @return int
     */
    public function getId();

    /**
     * Returns value of from supplied sources
     *
     * @param string $field
     * @param null $default
     * @return mixed
     */
    public function getValue($field, $default = null);

    /**
     * Moves cursor to the next identifier
     *
     * @return bool
     */
    public function next();
}
