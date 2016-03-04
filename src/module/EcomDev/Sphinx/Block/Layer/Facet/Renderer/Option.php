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
     * @param bool $withRel
     * @return string
     */
    public function getOptionUrl(OptionInterface $option, $without = [], $withRel = false)
    {
        $filter = $option->getFacet()->getFilterValue($option);
        $options = [];
        if ($filter === false || $filter === null || $filter === '') {
            $without[] = $option->getFacet()->getFilterField();
        } else {
            $options[$option->getFacet()->getFilterField()] = $filter;
        }

        return $this->urlBuilder->getUrl($options, $without, true, $withRel);
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
