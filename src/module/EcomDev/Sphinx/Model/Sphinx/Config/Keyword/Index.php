<?php

class EcomDev_Sphinx_Model_Sphinx_Config_Keyword_Index
    extends EcomDev_Sphinx_Model_Sphinx_Config_Index
    implements EcomDev_Sphinx_Model_Sphinx_ConfigInterface
{

    protected function _getStatements()
    {
        $config = array(
            'sources' => array(),
            'indexes' => array()
        );

        $indexPath = rtrim($this->_getConfig()->getIndexPath(), '/');

        $baseType = 'csv';

        $config['sources']['product_base'] = $this->getBaseIndexSource('keyword_product', $baseType);

        $fieldConfiguration = $this->getService()->getConfiguration('keyword_product');
        $fields = [];

        foreach ($fieldConfiguration->getFields() as $field) {
            $name = $field->getName();
            if ($field instanceof EcomDev_Sphinx_Model_Index_Field_Option && $field->isText()) {
                $name = sprintf('s_%s_label', $name);
            }

            $fields[] = $name;
        }

        $indexCollection = Mage::getResourceSingleton('ecomdev_sphinx/sphinx_config_index_collection');

        /** @var Mage_Core_Model_Store $store */
        foreach ($indexCollection->getStoreIds() as $storeId) {
            $config['sources'][sprintf('product_%s : product_base', $storeId)] = $this->getCommandSource(
                'keyword:product', $baseType, $storeId, [
                    '--visibility', 'catalog',
                    '--fields', implode(',', $fields)
                ]
            );

            $stemmerConfig = $this->getStemmerConfig($storeId);

            $config['indexes'][sprintf('product_%s', $storeId)] = array(
                sprintf('source = product_%s', $storeId),
                sprintf('path = %s/%s_%s', $indexPath, 'product', $storeId)
            ) + $stemmerConfig;
        }

        return $config;
    }
}
