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
    /**
     * Id of category name attribute
     *
     * @var int
     */
    private $nameAttributeId;

    /**
     * Helper
     *
     * @var EcomDev_Sphinx_Helper_Data
     */
    private $helper;

    /**
     * Resource initialization
     *
     */
    protected function _construct()
    {
        parent::_construct();
        $this->nameAttributeId = Mage::getSingleton('eav/config')->getAttribute(Mage_Catalog_Model_Category::ENTITY, 'name')->getId();
        $this->helper = Mage::helper('ecomdev_sphinx');
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

        $this->enableIndexSwitch();

        $select = new EcomDev_Sphinx_Model_Resource_Index_Reader_Select($this->_getReadAdapter());
        $select
            ->from(['index' => $this->getTable('catalog/category_product_index')], [
                'product_id', 'category_id', 'position', 'is_parent'
            ])
            ->join(
                ['entity_id' => $this->getMainMemoryTable('entity_id')],
                'entity_id.id = index.product_id',
                []
            )
            ->join(
                ['category' => $this->getTable('catalog/category')],
                'category.entity_id = index.category_id',
                ['level']
            )
        ;



        $scope->getFilter('store_id')->render('index', $select);
        $data = [];

        $names = [];
        
        foreach ($this->_getReadAdapter()->query($select) as $row) {
            if (!isset($names[$row['category_id']]) && $row['level'] > 1) {
                $names[$row['category_id']] = new CategoryName();
            }

            $categoryMatch = $this->helper->getCategoryMatch($row['category_id']);

            $data[$row['product_id']]['_category_position'][$categoryMatch] = (int)$row['position'];


            if ($row['position'] !== '0' && $row['is_parent']) {
                $data[$row['product_id']]['_direct_position'][$categoryMatch] = (int)$row['position'];
                if (!isset($data[$row['product_id']]['_best_direct_position'])
                    || $data[$row['product_id']]['_best_direct_position'] > $row['position']) {
                    $data[$row['product_id']]['_best_direct_position'] = (int)$row['position'];
                }
            }

            if ($row['position'] !== '0') {
                $data[$row['product_id']]['_anchor_position'][$categoryMatch] = (int)$row['position'];
                if (!isset($data[$row['product_id']]['_best_anchor_position'])
                    || $data[$row['product_id']]['_best_anchor_position'] > $row['position']) {
                    $data[$row['product_id']]['_best_anchor_position'] = (int)$row['position'];
                }
            }

            if ($row['is_parent']) {
                $data[$row['product_id']]['_direct_category_ids'][$categoryMatch] = $row['category_id'];
            }

            $data[$row['product_id']]['_anchor_category_ids'][$categoryMatch] = $row['category_id'];

            if ($row['level'] > 1) {
                if ($row['is_parent']) {
                    $data[$row['product_id']]['_direct_category_names'][$categoryMatch] = $names[$row['category_id']];
                }

                $data[$row['product_id']]['_anchor_category_names'][$categoryMatch] = $names[$row['category_id']];
            }
        }

        if ($names) {
            $this->fillMemoryTable('category_id', array_keys($names));

            $select->reset();
            $select
                ->indexHint(
                    'name',
                    $this->findIndexHint(
                        $this->getTable(['catalog/category', 'varchar']),
                        ['entity_id', 'attribute_id', 'store_id']
                    )
                )
                ->from(
                    ['name' => $this->getTable(['catalog/category', 'varchar'])],
                    ['entity_id', 'value']
                )
                ->join(['entity_id' => $this->getMemoryTableName('category_id')], 'entity_id.id = name.entity_id', [])
                ->where('name.store_id IN(0, :store_id)')
                ->where('name.attribute_id = :attribute_id')
                ->order('name.store_id ASC');


            $nameData = $this->_getReadAdapter()->fetchPairs($select, [
                'store_id' => $scope->getFilter('store_id')->getValue(),
                'attribute_id' => $this->nameAttributeId
            ]);

            foreach ($nameData as $categoryId => $name) {
                if (isset($names[$categoryId])) {
                    $names[$categoryId]->setName($name);
                }
            }

            $select
                ->reset()
                ->from(
                    ['entity_id' => $this->getMainMemoryTable('entity_id')],
                    []
                )
                ->joinCross(
                    ['category_id' => $this->getMemoryTableName('category_id')],
                    []
                )
                ->join(
                    ['category_index' => $this->getTable('catalog/category_product_index')],
                    implode(' and ', [
                        'category_index.product_id = entity_id.id',
                        'category_index.category_id = category_id.id'
                    ]),
                    []
                )
                ->columns([
                    'id_path' => 'CONCAT(:product_path, category_index.product_id, :slash, category_index.category_id)'
                ])
                ->bind([
                    'product_path' => 'product/',
                    'slash' => '/'
                ])
            ;

            $scope->getFilter('store_id')->render('category_index', $select);

            $idPathFilter = $this->createTemporaryTableFromSelect($select, ['PRIMARY' => ['id_path']]);

            $select->reset()
                ->from(['url' => $this->getTable('core/url_rewrite')], ['product_id', 'category_id', 'request_path'])
                ->join(['id_path' => $idPathFilter], 'url.id_path = id_path.id_path', [])
                ->where('url.is_system = ?', 1);

            $scope->getFilter('store_id')->render('url', $select);

            foreach ($this->_getReadAdapter()->query($select) as $row) {
                $categoryMatch = $this->helper->getCategoryMatch($row['category_id']);
                $data[$row['product_id']]['_category_url'][$categoryMatch] = $row['request_path'];
            }

            $this->dropTemporaryTable($idPathFilter);
        }

        $this->disableIndexSwitch();
        return $data;
    }
}
