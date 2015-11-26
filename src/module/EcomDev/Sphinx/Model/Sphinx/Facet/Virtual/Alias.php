<?php

class EcomDev_Sphinx_Model_Sphinx_Facet_Virtual_Alias
    extends EcomDev_Sphinx_Model_Sphinx_Facet_Attribute_Option
{
    public function __construct(\EcomDev_Sphinx_Model_Attribute $attribute, $filterName, $label)
    {
        parent::__construct($attribute, $filterName, $filterName, $label);
    }

}
