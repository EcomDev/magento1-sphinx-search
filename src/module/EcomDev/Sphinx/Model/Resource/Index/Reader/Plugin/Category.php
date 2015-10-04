<?php

use EcomDev_Sphinx_Contract_Reader_ScopeInterface as ScopeInterface;

/**
 * Price index data retriever
 *
 *
 */
class EcomDev_Sphinx_Model_Resource_Index_Reader_Plugin_Category
    extends EcomDev_Sphinx_Model_Resource_Index_Reader_Plugin_AbstractPlugin
{
    /**
     * Returns array of data per entity identifier
     *
     * @param int[] $identifiers
     * @param ScopeInterface $scope
     * @return array[]
     */
    public function read(array $identifiers, ScopeInterface $scope)
    {
        if (!$scope->hasFilter('store_id') || !$identifiers) {
            return [];
        }

        $select = $this->_getReadAdapter()->select();
        $select
            ->from(['index' => $this->getTable('catalog/category_product_index')], [
                'product_id', 'category_id', 'position', 'is_parent'
            ])
            ->join(
                ['entity' => $this->getTable('catalog/category')],
                'entity.entity_id = index.category_id',
                ['level', 'path']
            )
            ->join(
                ['default_name' => $this->getTable(['catalog/category', 'varchar'])],
                'default_name.entity_id = index.category_id and default_name.store_id = 0',
                []
            )
            ->join(
                ['attribute' => $this->getTable('eav/attribute')],
                $this->_getReadAdapter()->quoteInto(
                    'attribute.attribute_id = default_name.attribute_id and attribute.attribute_code = ?',
                    'name'
                ),
                []
            )
            ->joinLeft(
                ['store_name' => $this->getTable(['catalog/category', 'varchar'])],
                'store_name.entity_id = default_name.entity_id '
                . ' and store_name.attribute_id = attribute.attribute_id'
                . ' and store_name.store_id = index.store_id',
                ['name' => 'IF(store_name.value_id IS NULL, default_name.value, store_name.value)']
            )
        ;

        $scope->getFilter('store_id')->render('index', $select);

        $select->where('index.product_id IN(?)', $identifiers);

        $data = [];
        $categoryIds = [];
        foreach ($this->_getReadAdapter()->query($select) as $row) {
            $categoryIds[$row['category_id']] = $row['category_id'];
            $data[$row['product_id']]['_categories'][$row['category_id']] = $row;
        }

        if (!$categoryIds) {
            return $data;
        }


        $select->reset()
            ->from(['url' => $this->getTable('core/url_rewrite')], ['product_id', 'category_id', 'request_path'])
            ->where('url.category_id IN(?)', $categoryIds)
            ->where('url.product_id IN(?)', $identifiers)
            ->where('url.is_system = ?', 1);

        $scope->getFilter('store_id')->render('url', $select);

        foreach ($this->_getReadAdapter()->query($select) as $row) {
            $data[$row['product_id']]['_category_urls'][$row['category_id']] = $row['request_path'];
        }

        return $data;
    }
}
