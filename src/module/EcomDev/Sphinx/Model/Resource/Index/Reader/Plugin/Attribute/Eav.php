<?php
use EcomDev_Sphinx_Contract_Reader_ScopeInterface as ScopeInterface;

class EcomDev_Sphinx_Model_Resource_Index_Reader_Plugin_Attribute_Eav
    extends EcomDev_Sphinx_Model_Resource_Index_Reader_Plugin_AbstractPlugin
    implements EcomDev_Sphinx_Contract_Reader_PluginInterface,
        EcomDev_Sphinx_Contract_Reader_SnapshotAwareInterface
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
     * Snapshot
     *
     * @var EcomDev_Sphinx_Contract_Reader_SnapshotInterface
     */
    private $snapshot;

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

    public function setSnapshot(EcomDev_Sphinx_Contract_Reader_SnapshotInterface $snapshot)
    {
        $this->snapshot = $snapshot;
        return $this;
    }

    public function getSnapshot()
    {
        return $this->snapshot;
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
                    'source_model = :source_table or '
                    . ' (backend_type = :int and frontend_input = :select and source_model is null) or '
                    . ' (backend_type = :varchar and frontend_input = :multiselect and source_model is null)',
                    '1',
                    '0'
                )
            ]
        );

        $select->where('attribute.entity_type_id = ?', $this->entityTypeId);
        $select->where('attribute.attribute_code IN(?)', $attributeCodes);

        $this->attributeCache['info'] = [];
        $this->attributeCache['has_multiple'] = false;

        foreach ($this->_getReadAdapter()->query($select, [
            'source_table' => 'eav/entity_attribute_source_table',
            'int' => 'int',
            'select' => 'select',
            'varchar' => 'varchar',
            'multiselect' => 'multiselect'
        ]) as $row) {
            $this->attributeCache['info'][$row['attribute_id']] = $row;

            if ($row['is_multiple']) {
                $this->attributeCache['has_multiple'] = true;

            }
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

        if (!$attributeCodes || !$scope->hasFilter('store_id') || !$this->getSnapshot() || !$identifiers) {
            return [];
        }

        $this->loadAttributeInfo($attributeCodes);

        if (!$this->entityMemoryTable) {
            $this->fillMemoryTable('entity_id', $identifiers);
        }

        $data = [];

        $options = Mage::getResourceModel('ecomdev_sphinx/index_reader_plugin_attribute_eav_option_hash');

        foreach ($this->getMergedAttributeValues() as $row) {
            $attributeCode = $this->attributeCache['info'][$row['attribute_id']]['attribute_code'];

            if ($this->attributeCache['info'][$row['attribute_id']]['is_multiple']) {
                if ($row['has_comma'] === '1') {
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

        foreach ($this->getMergedAttributeValues(true) as $row) {
            $attributeCode = $this->attributeCache['info'][$row['attribute_id']]['attribute_code'];

            if ($row['has_comma'] === '1') {
                $value = array_filter(explode(',', $row['value']));
                $value = array_combine($value, $value);
            } else {
                $value = $row['value'];
            }

            if ($this->attributeCache['info'][$row['attribute_id']]['is_multiple']) {
                $options->addOptionValues($value, $attributeCode);
            }

            $data[$row['entity_id']]['_' . $attributeCode . '_label'] = $options;

            if (isset($data[$row['entity_id']][$attributeCode])) {
                $existingValue = $this->normalizeArrayValue($data[$row['entity_id']][$attributeCode]);
                $value = $this->normalizeArrayValue($value);

                if (empty($value) && empty($existingValue)) {
                    $data[$row['entity_id']][$attributeCode] = [];
                }

                $data[$row['entity_id']][$attributeCode] = $existingValue + $value;
            } else {
                $data[$row['entity_id']][$attributeCode] = $value;
            }
        }

        $options->loadOptions($scope->getFilter('store_id')->getValue());
        return $data;
    }

    private function normalizeArrayValue($value)
    {
        if (!is_array($value) && !empty($value)) {
            $value = [$value => $value];
        } elseif (!is_array($value)) {
            $value = [];
        }

        return $value;
    }

    private function getMergedAttributeValues($isChild = false)
    {
        $storeSelect = $this->getAttributeValueSelect($isChild);

        $data = [];

        foreach ($this->_getReadAdapter()->query($storeSelect) as $row) {
            $data[$row['main_id'] . '_' . $row['attribute_id']] = $row;
        }

        return $data;
    }

    /**
     * Returns attribute value select
     *
     * @param bool $isChild
     *
     * @return Varien_Db_Select
     */
    private function getAttributeValueSelect($isChild = false)
    {
        $table = $this->getSnapshot()->getSnapshotTable($this->backendType);

        $select = $this->_getReadAdapter()->select();
        $select
            ->from(['value' => $table], []);

        if ($isChild) {
            $select
                ->join(
                    ['relation' => $this->getTable('catalog/product_relation')],
                    'relation.child_id = value.entity_id',
                    []
                )
                ->join(
                    ['entity_id' => $this->getMainMemoryTable('entity_id')],
                    'entity_id.id = relation.parent_id',
                    []
                )
            ;
            $select->where('value.is_child_data = ?', 1);
            $select->where('value.is_child_data_stock = ?', 1);
        } else {
            $select->join(
                ['entity_id' => $this->getMainMemoryTable('entity_id')],
                'value.entity_id = entity_id.id',
                []
            );
        }

        $select
            ->columns([
                'entity_id' => 'entity_id.id',
                'attribute_id' => 'value.attribute_id',
                'value' => new Zend_Db_Expr('TRIM(IFNULL(value.value, \'\'))'),
                'has_comma' => new Zend_Db_Expr('LOCATE(\',\', IFNULL(value.value, \'\'))')
            ])
        ;

        $select->columns(['main_id' => 'value.entity_id']);

        return $select;
    }
}
