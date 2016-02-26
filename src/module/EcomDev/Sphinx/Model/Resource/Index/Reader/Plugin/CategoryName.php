<?php

class EcomDev_Sphinx_Model_Resource_Index_Reader_Plugin_CategoryName
{
    /**
     * Name placeholder
     *
     * @var string
     */
    private $name = '';

    /**
     * Sets category name
     *
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * String representation of name
     */
    public function __toString()
    {
        return $this->name;
    }
}
