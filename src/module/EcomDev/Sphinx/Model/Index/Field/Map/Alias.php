<?php
<?php

use EcomDev_Sphinx_Contract_DataRowInterface as DataRowInterface;
use EcomDev_Sphinx_Contract_Reader_ScopeInterface as ScopeInterface;

class EcomDev_Sphinx_Model_Index_Field_Map_Alias
    extends EcomDev_Sphinx_Model_Index_AbstractField
{
    /**
     * Source attributes
     *
     * @var string[]
     */
    private $source;

    public function __construct($name, $source, $mapping)
    {
        parent::__construct(self::TYPE_ATTRIBUTE_MULTI, $name);

    }
}
