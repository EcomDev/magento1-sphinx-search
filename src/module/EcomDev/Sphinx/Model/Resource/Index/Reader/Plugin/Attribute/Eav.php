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

        if (isset($this->knownPrefix[$entityType])) {
            $this->entityTable = $this->knownPrefix[$entityType];
        }
        parent::__construct();
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

        $select = $this->_getReadAdapter()->select();

        $select->from(
            ['attribute' => $this->getTable('eav/attribute')],
            [
                'attribute_id',
                'attribute_code',
                'is_multiple' => $this->_getReadAdapter()->getCheckSql(
                    $this->_getReadAdapter()->quoteInto(
                        'source_model = ?',
                        'eav/entity_attribute_source_table'
                    ),
                    '1',
                    '0'
                )
            ]
        );

        $select->where('attribute.attribute_code IN(?)', $attributeCodes);

        $multiAttributeIds = [];
        $attributeInfo = [];
        foreach ($this->_getReadAdapter()->query($select) as $row) {
            $attributeInfo[$row['attribute_id']] = $row;
            if ($row['is_multiple']) {
                $multiAttributeIds[] = $row['attribute_id'];
            }
        }

        $conditions = [
            'store_value.attribute_id = default_value.attribute_id',
            'store_value.entity_id = default_value.entity_id',
            $this->_getReadAdapter()->quoteInto(
                'store_value.store_id = ?', $scope->getFilter('store_id')->getValue()
            )
        ];

        $valueSelect = $this->getAttributeValueSelect($conditions, array_keys($attributeInfo));
        $valueSelect->columns([
            'entity_id' => 'default_value.entity_id',
            'attribute_id' => 'default_value.attribute_id',
            'value' => new Zend_Db_Expr(
                'IF(store_value.value_id IS NULL, default_value.value, store_value.value)'
            )
        ]);

        $valueSelect->where('default_value.entity_id IN(?)', $identifiers);

        $data = [];

        foreach ($this->_getReadAdapter()->query($valueSelect) as $row) {
            $isMultiple = $attributeInfo[$row['attribute_id']]['is_multiple'];
            $attributeCode = $attributeInfo[$row['attribute_id']]['attribute_code'];

            if ($isMultiple) {
                $data[$row['entity_id']][$attributeCode] = explode(',', trim($row['value']));
            } else {
                $data[$row['entity_id']][$attributeCode] = $row['value'];
            }
        }

        if (!$multiAttributeIds || $this->entityType !== Mage_Catalog_Model_Product::ENTITY) {
            return $data;
        }

        $valueSelect = $this->getAttributeValueSelect(
            $conditions, $multiAttributeIds
        );

        $valueSelect->join(
            ['relation' => $this->getTable('catalog/product_relation')],
            'relation.child_id = default_value.entity_id',
            []
        );

        $valueSelect->where('relation.parent_id IN(?)', $identifiers);

        $valueSelect->columns([
            'entity_id' => 'relation.parent_id',
            'attribute_id' => 'default_value.attribute_id',
            'value' => new Zend_Db_Expr(
                'IF(store_value.value_id IS NULL, default_value.value, store_value.value)'
            )
        ]);

        foreach ($this->_getReadAdapter()->query($valueSelect) as $row) {
            $attributeCode = $attributeInfo[$row['attribute_id']]['attribute_code'];
            $value = explode(',', trim($row['value']));
            if (isset($data[$row['entity_id']][$attributeCode])) {
                $data[$row['entity_id']][$attributeCode] = array_merge(
                    $data[$row['entity_id']][$attributeCode],
                    $value
                );
            } else {
                $data[$row['entity_id']][$attributeCode] = $value;
            }
        }

        return $data;
    }

    /**
     * Returns attribute value select
     *
     * @param string[] $conditions
     * @param int[] $attributeIds
     * @return Varien_Db_Select
     */
    private function getAttributeValueSelect($conditions, $attributeIds)
    {
        $entityValueTable = $this->getTable([$this->entityTable, $this->backendType]);
        $select = $this->_getReadAdapter()->select();
        $select
            ->from(['default_value' => $entityValueTable],[])
            ->joinLeft(['store_value' => $entityValueTable], implode(' AND ', $conditions), [])
            ->where('default_value.attribute_id IN(?)', $attributeIds)
            ->where('default_value.store_id = ?', 0);

        return $select;
    }
}
