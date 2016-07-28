<?php

class EcomDev_Sphinx_Helper_Data
    extends Mage_Core_Helper_Abstract
{
    /**
     * Prefix for category match prefixes
     *
     * @var string
     */
    const CATEGORY_MATCH_PREFIX = 'cat_';

    /**
     * @return string
     */
    public function getAutocompleteUrl()
    {
        return $this->_getUrl('sphinx/autocomplete/', ['format' => 'html']);
    }

    /**
     * Returns a category match string
     *
     * @param int|string $categoryId
     *
     * @return string
     */
    public function getCategoryMatch($categoryId)
    {
        return sprintf('%s%s', self::CATEGORY_MATCH_PREFIX, $categoryId);
    }
}
