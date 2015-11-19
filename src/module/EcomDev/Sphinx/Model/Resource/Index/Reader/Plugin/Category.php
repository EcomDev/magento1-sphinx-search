<?php

use EcomDev_Sphinx_Contract_Reader_ScopeInterface as ScopeInterface;
use EcomDev_Sphinx_Model_Resource_Index_Reader_Plugin_CategoryName as CategoryName;

/**
 * Price index data retriever
 *
 *
 */
class EcomDev_Sphinx_Model_Resource_Index_Reader_Plugin_Category
    extends EcomDev_Sphinx_Model_Resource_Index_Reader_Plugin_AbstractPlugin
{
    private $nameAttributeId;

    /**
     * Resource initialization
     *
     */
    protected function _construct()
    {
         parent::_construct();
         $this->nameAttributeId = Mage::getSingleton('eav/config')->getAttribute(Mage_Catalog_Model_Category::ENTITY, 'name');
    }


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

        if (!$this->entityMemoryTable) {
            $this->fillMemoryTable('entity_id', $identifiers);
        }

        $select = $this->_getReadAdapter()->select();
        $select
            ->from(['index' => $this->getTable('catalog/category_product_index')], [
                'product_id', 'category_id', 'position', 'is_parent'
            ])
            ->join(
                ['entity_id' => $this->getMainMemoryTable('entity_id')],
                'entity_id.id = index.product_id',
                []
            )
        ;

        $scope->getFilter('store_id')->render('index', $select);
        $data = [];

        $names = [];
        foreach ($this->_getReadAdapter()->query($select) as $row) {
            if (!isset($names[$row['category_id']])) {
                $names[$row['category_id']] = new CategoryName();
            }

            $data[$row['product_id']]['_category_position']['cat_' . $row['category_id']] = $row['position'];

            if ($row['position'] !== '0' && $row['is_parent']) {
                $data[$row['product_id']]['_direct_position']['cat_' . $row['category_id']] = $row['position'];
                if (!isset($data[$row['product_id']]['_best_direct_position'])
                    || $data[$row['product_id']]['_best_direct_position'] > $row['position']) {
                    $data[$row['product_id']]['_best_direct_position'] = $row['position'];
                }
            }

            if ($row['position'] !== '0') {
                $data[$row['product_id']]['_anchor_position']['cat_' . $row['category_id']] = $row['position'];
                if (!isset($data[$row['product_id']]['_best_anchor_position'])
                    || $data[$row['product_id']]['_best_anchor_position'] > $row['position']) {
                    $data[$row['product_id']]['_best_anchor_position'] = $row['position'];
                }
            }

            if ($row['is_parent']) {
                $data[$row['product_id']]['_direct_category_ids']['cat_' . $row['category_id']] = $row['category_id'];
                $data[$row['product_id']]['_direct_category_names']['cat_' . $row['category_id']] = $names[$row['category_id']];
            }

            $data[$row['product_id']]['_anchor_category_ids']['cat_' . $row['category_id']] = $row['category_id'];
            $data[$row['product_id']]['_anchor_category_names']['cat_' . $row['category_id']] = $names[$row['category_id']];
        }

        if ($names) {
            $this->fillMemoryTable('category_id', array_keys($names));

            $select->reset()
                ->from(
                    ['name' => $this->getTable(['catalog/category', 'varchar'])],
                    ['entity_id', 'value']
                )
                ->join(['entity_id' => $this->getMemoryTableName('category_id')], 'entity_id.id = name.entity_id', [])
                ->where('name.store_id = :store_id')
                ->where('name.attribute_id = ?', $this->nameAttributeId);

            $nameData = [];
            foreach ([$scope->getFilter('store_id')->getValue(), 0] as $storeId) {
                $nameData += $this->_getReadAdapter()->fetchPairs($select, ['store_id' => $storeId]);
            }

            foreach ($nameData as $categoryId => $name) {
                if (isset($names[$categoryId])) {
                    $names[$categoryId]->setName($name);
                }
            }
        }

        $select->reset()
            ->from(['url' => $this->getTable('core/url_rewrite')], ['product_id', 'category_id', 'request_path'])
            ->join(
                ['entity_id' => $this->getMainMemoryTable('entity_id')],
                'entity_id.id = url.product_id',
                []
            )
            ->join(
                ['category_index' => $this->getTable('catalog/category_product_index')],
                'category_index.product_id = entity_id.id and category_index.category_id = url.category_id and category_index.store_id = url.store_id',
                []
            )
            ->where('url.is_system = ?', 1);

        $scope->getFilter('store_id')->render('url', $select);

        foreach ($this->_getReadAdapter()->query($select) as $row) {
            $data[$row['product_id']]['_category_url']['cat_' . $row['category_id']] = $row['request_path'];
        }

        return $data;
    }
}
