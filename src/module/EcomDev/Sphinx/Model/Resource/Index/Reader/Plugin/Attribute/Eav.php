<?php
use EcomDev_Sphinx_Contract_Reader_ScopeInterface as ScopeInterface;

class EcomDev_Sphinx_Model_Resource_Index_Reader_Plugin_Attribute_Eav
    extends EcomDev_Sphinx_Model_Resource_Index_Reader_Plugin_AbstractPlugin
    implements EcomDev_Sphinx_Contract_Reader_PluginInterface
{
    /**
     * Eav entity type for reference
     *
     * @var string
     */
    private $entityType;

    /**
     * Eav entity type for reference
     *
     * @var string
     */
    private $entityTable;

    /**
     * Backend type which is used
     *
     * @var string
     */
    private $backendType;

    /**
     * @var array
     */
    private $attributeCache = [];

    /**
     * Entity type id
     *
     * @var int
     */
    private $entityTypeId;

    /**
     * List of known table prefixes
     *
     * @var string[]
     */
    private $knownPrefix = [
        Mage_Catalog_Model_Product::ENTITY => 'catalog/product',
        Mage_Catalog_Model_Category::ENTITY => 'catalog/category'
    ];

    /**
     * Sets type of data to retrieve
     *
     * @param string $entityType
     * @param string $backendType
     */
    public function __construct($entityType, $backendType)
    {
        $this->entityType = $entityType;
        $this->backendType = $backendType;
        $this->entityTypeId = Mage::getSingleton('eav/config')->getEntityType($this->entityType)->getId();

        if (isset($this->knownPrefix[$entityType])) {
            $this->entityTable = $this->knownPrefix[$entityType];
        }

        parent::__construct();
    }


    /**
     * @param string $attributeCodes
     * @return $this
     */
    private function loadAttributeInfo($attributeCodes)
    {
        if (isset($this->attributeCache['checksum']) && $this->attributeCache['checksum'] === $attributeCodes) {
            return $this;
        }

        $this->attributeCache['checksum'] = $attributeCodes;
        $select = $this->_getReadAdapter()->select();

        $select->from(
            ['attribute' => $this->getTable('eav/attribute')],
            [
                'attribute_id',
                'attribute_code',
                'source_model',
                'is_multiple' => $this->_getReadAdapter()->getCheckSql(
                    'source_model = :source_table or (backend_type = :int and frontend_input = :select and source_model is null)',
                    '1',
                    '0'
                )
            ]
        );

        $select->where('attribute.entity_type_id = ?', $this->entityTypeId);
        $select->where('attribute.attribute_code IN(?)', $attributeCodes);


        $this->attributeCache['info'] = [];
        $this->attributeCache['has_multiple'] = false;
        $attributeIds = [];
        $multiValueAttributeIds = [];

        foreach ($this->_getReadAdapter()->query($select, [
            'source_table' => 'eav/entity_attribute_source_table',
            'int' => 'int',
            'select' => 'select'
        ]) as $row) {
            $this->attributeCache['info'][$row['attribute_id']] = $row;

            $attributeIds[] = $row['attribute_id'];

            if ($row['is_multiple']) {
                $multiValueAttributeIds[] = $row['attribute_id'];
                $this->attributeCache['has_multiple'] = true;
            }
        }

        $this->fillMemoryTable('attribute_id', $attributeIds);

        if ($this->attributeCache['has_multiple']) {
            $this->fillMemoryTable('attribute_id_multiple', $multiValueAttributeIds);
        }

        return $this;
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
        $attributeCodes = $scope->getConfiguration()->getAttributeCodes($this->backendType);

        if (!$attributeCodes || !$scope->hasFilter('store_id') || !$identifiers) {
            return [];
        }

        $this->loadAttributeInfo($attributeCodes);

        if (!$this->entityMemoryTable) {
            $this->fillMemoryTable('entity_id', $identifiers);
        }

        $storeId = $scope->getFilter('store_id')->getValue();

        $data = [];

        $options = Mage::getResourceModel('ecomdev_sphinx/index_reader_plugin_attribute_eav_option_hash');

        foreach ($this->getMergedAttributeValues('attribute_id', $storeId) as $row) {
            $isMultiple = $this->attributeCache['info'][$row['attribute_id']]['is_multiple'];
            $attributeCode = $this->attributeCache['info'][$row['attribute_id']]['attribute_code'];

            if ($isMultiple) {
                if ($row['is_multi_value'] !== '0') {
                    $value = array_filter(explode(',', $row['value']));
                    $value = array_combine($value, $value);
                } else {
                    $value = $row['value'];
                }

                $data[$row['entity_id']][$attributeCode] = $value;
                $data[$row['entity_id']]['_' . $attributeCode . '_label'] = $options;
                $options->addOptionValues($value, $attributeCode);
            } else {
                $data[$row['entity_id']][$attributeCode] = $row['value'];
            }


        }

        if (!$this->attributeCache['has_multiple'] || $this->entityType !== Mage_Catalog_Model_Product::ENTITY) {
            return $data;
        }

        foreach ($this->getMergedAttributeValues('attribute_id_multiple', $storeId, true) as $row) {
            $attributeCode = $this->attributeCache['info'][$row['attribute_id']]['attribute_code'];

            if ($row['is_multi_value'] !== '0') {
                $value = array_filter(explode(',', $row['value']));
                $value = array_combine($value, $value);
            } else {
                $value = $row['value'];
            }

            $data[$row['entity_id']]['_' . $attributeCode . '_labels'] = $options;
            $options->addOptionValues($value, $attributeCode);

            if (isset($data[$row['entity_id']][$attributeCode])) {
                $existingValue = $data[$row['entity_id']][$attributeCode];

                if (!is_array($existingValue) && empty($existingValue)) {
                    $existingValue = [];
                } elseif (!is_array($existingValue)) {
                    $existingValue = [$existingValue => $existingValue];
                }

                if (!is_array($value) && !empty($value)) {
                    $value = [$value => $value];
                } elseif (!empty($value)) {
                    $value = [];
                }

                if (empty($value) && empty($existingValue)) {
                    $data[$row['entity_id']][$attributeCode] = [];
                }

                $data[$row['entity_id']][$attributeCode] = $existingValue + $value;
            } else {
                $data[$row['entity_id']][$attributeCode] = $value;
            }
        }

        $options->loadOptions($storeId);
        return $data;
    }

    private function getMergedAttributeValues($attributeTable, $storeId, $isChild = false)
    {
        $defaultSelect = $this->getAttributeValueSelect($attributeTable, 0, $isChild);
        $storeSelect = $this->getAttributeValueSelect($attributeTable, $storeId, $isChild);

        $data = [];
        foreach ($this->_getReadAdapter()->query($defaultSelect) as $row) {
            $data[$row['main_id'] . '_' . $row['attribute_id']] = $row;
        }

        foreach ($this->_getReadAdapter()->query($storeSelect) as $row) {
            $data[$row['main_id'] . '_' . $row['attribute_id']] = $row;
        }

        return $data;
    }

    /**
     * Returns attribute value select
     *
     * @param string $attributeTable
     * @param int $storeId
     * @param bool $isChild
     *
     * @return Varien_Db_Select
     */
    private function getAttributeValueSelect($attributeTable, $storeId, $isChild = false)
    {
        $entityValueTable = $this->getTable([$this->entityTable, $this->backendType]);
        $select = $this->_getReadAdapter()->select();
        $select
            ->from(['default_value' => $entityValueTable],[]);

        if ($isChild) {
            $select->join(['relation' => $this->getTable('catalog/product_relation')], 'relation.child_id = default_value.entity_id', []);
            $select->join(['product' => $this->getTable('catalog/product')], 'product.entity_id = relation.parent_id', []);
            $select->join(['entity_id' => $this->getMainMemoryTable('entity_id')], 'entity_id.id = relation.parent_id', []);
            $select->join(['product_super' => $this->getTable('catalog/product_super_attribute')], 'product_super.product_id = entity_id.id and product_super.attribute_id = default_value.attribute_id', []);
            $select->where('product.type_id = ?', Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE);
        } else {
            $select->join(['entity_id' => $this->getMainMemoryTable('entity_id')], 'default_value.entity_id = entity_id.id', []);
        }

        $select->join(['attribute_id' => $this->getMemoryTableName($attributeTable)], 'default_value.attribute_id = attribute_id.id', [])
            ->columns([
                'entity_id' => 'entity_id.id',
                'attribute_id' => 'default_value.attribute_id',
                'value' => new Zend_Db_Expr('TRIM(default_value.value)'),
                'is_multi_value' => new Zend_Db_Expr('LOCATE(default_value.value, \',\')'),
            ])
            ->where('default_value.store_id = ?', $storeId)
        ;

        if ($isChild) {
            $select->columns(['main_id' => 'relation.child_id']);
        } else {
            $select->columns(['main_id' => 'entity_id.id']);
        }

        return $select;
    }
}
