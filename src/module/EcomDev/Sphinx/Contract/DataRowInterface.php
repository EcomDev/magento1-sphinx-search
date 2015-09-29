<?php

interface EcomDev_Sphinx_Contract_DataRowInterface
{
    /**
     * Sets identifier of a row
     *
     * @return int
     */
    public function setId($id);

    /**
     * Sets key which will be used for retrieval of data from index
     *
     * @param string $key
     * @return $this
     */
    public function setKey($key);

    /**
     * Returns value of from supplied sources
     *
     * @param string $field
     * @param null $default
     * @return mixed
     */
    public function getValue($field, $default = null);
}
