<?php

class EcomDev_Sphinx_Helper_Data
    extends Mage_Core_Helper_Abstract
{
    /**
     * @return string
     */
    public function getAutocompleteUrl()
    {
        return $this->_getUrl('sphinx/autocomplete/', ['format' => 'html']);
    }
}
