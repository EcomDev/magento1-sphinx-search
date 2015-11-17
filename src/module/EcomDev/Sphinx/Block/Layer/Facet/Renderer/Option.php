<?php

use EcomDev_Sphinx_Model_Sphinx_Facet_Filter_OptionInterface as OptionInterface;

/**
 * Renderer option interface
 *
 */
class EcomDev_Sphinx_Block_Layer_Facet_Renderer_Option
    extends EcomDev_Sphinx_Block_Layer_Facet_AbstractRenderer
{
    /**
     * Returns list of options
     *
     * @return OptionInterface[]
     */
    public function getOptions()
    {
        return $this->getFacet()->getOptions();
    }

    /**
     * Option url for a filter
     *
     * @param OptionInterface $option
     * @param string[] $without
     * @return string
     */
    public function getOptionUrl(OptionInterface $option, $without = [])
    {
        $baseUrl = $this->getLayer()->getCurrentUrl();
        $activeFilters = $this->getLayer()->getActiveFilters()
            + $this->getLayer()->getAdditionalQuery();
        $filter = $option->getFacet()->getFilterValue($option);
        if ($filter === false || $filter === null || $filter === '') {
            unset($activeFilters[$option->getFacet()->getFilterField()]);
        } else {
            $activeFilters[$option->getFacet()->getFilterField()] = $filter;
        }

        if ($without) {
            foreach ($without as $name) {
                if (isset($activeFilters[$name])) {
                    unset($activeFilters[$name]);
                }
            }
        }

        return $baseUrl . ($activeFilters ? '?' . http_build_query($activeFilters, '', '&amp;') : '');
    }

    /**
     * Options JSON
     *
     * @param string $itemCssRule
     * @param string $hiddenClass
     * @param string $activeClass
     * @param string $collapseCssRule
     * @param string $expandCssRule
     * @return string
     */
    public function getOptionJson($itemCssRule, $hiddenClass, $activeClass, $collapseCssRule, $expandCssRule)
    {
        $options = [
            'id' => $this->getFacet()->getFilterField(),
            'optionLimit' => $this->getDataSetDefault('top_option_limit', 5),
            'optionByCount' => (bool)$this->getDataSetDefault('top_option_by_count', 1),
            'hiddenClass' => $hiddenClass,
            'itemCssRule' => $itemCssRule,
            'expandCssRule' => $expandCssRule,
            'collapseCssRule' => $collapseCssRule,
            'activeClass' => $activeClass,
            'animation' => (bool)$this->getDataSetDefault('animation', 1)
        ];

        return json_encode($options);
    }
}
