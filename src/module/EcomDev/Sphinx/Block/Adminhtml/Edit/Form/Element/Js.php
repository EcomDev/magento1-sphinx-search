<?php

class EcomDev_Sphinx_Block_Adminhtml_Edit_Form_Element_Js
    extends Varien_Data_Form_Element_Abstract
{
    public function getElementHtml()
    {
        $html = sprintf(
            '<div id="%s" %s></div>', $this->getHtmlId(), $this->serialize($this->getHtmlAttributes())
        );
        $html .= sprintf('<script type="text/javascript">%s</script>', $this->getElementJavaScriptClass());
        $html .= $this->getAfterElementHtml();
        return $html;
    }

    private function getElementJavaScriptClass()
    {
        $options = [
            'template' => $this->getData('js_template'),
            'name' => $this->getName(),
            'value' => $this->getValue()
        ];

        if (is_array($this->getData('js_options'))) {
            $options += $this->getData('js_options');
        }

        return sprintf(
            'new %s(%s, %s);',
            $this->getData('js_class'),
            json_encode($this->getHtmlId()),
            json_encode($options)
        );
    }

    public function getHtmlAttributes()
    {
        return array('class', 'style');
    }
}
