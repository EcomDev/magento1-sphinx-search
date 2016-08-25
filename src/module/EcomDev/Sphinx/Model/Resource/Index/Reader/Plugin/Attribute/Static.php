<?php

use EcomDev_Sphinx_Contract_Reader_ScopeInterface as ScopeInterface;

/**
 * Static attributes loader
 *
 */
class EcomDev_Sphinx_Model_Resource_Index_Reader_Plugin_Attribute_Static
    extends EcomDev_Sphinx_Model_Resource_Index_Reader_Plugin_AbstractPlugin
{
    /**
     * Table
     *
     * @var string
     */
    private $table;

    /**
     * Entity type
     *
     * @var string
     */
    private $entityType;

    /**
     * List of known table prefixes
     *
     * @var string[]
     */
    private $knownTypes = [
        Mage_Catalog_Model_Product::ENTITY => 'catalog/product',
        Mage_Catalog_Model_Category::ENTITY => 'catalog/category'
    ];

    /**
     * Based on entity type a table is found
     *
     * @param string $entityType
     */
    public function __construct($entityType)
    {
        parent::__construct();
        $this->entityType = $entityType;
        $this->table = $this->getTable($this->knownTypes[$this->entityType]);
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
        $attributeCodes = $scope->getConfiguration()->getAttributeCodes('static');
        if (!$attributeCodes || !$identifiers) {
            return [];
        }

        if (!in_array('entity_id', $attributeCodes, true)) {
            array_unshift($attributeCodes, 'entity_id');
        }

        if (!$this->entityMemoryTable) {
            $this->fillMemoryTable('entity_id', $identifiers);
        }

        $select = $this->_getReadAdapter()->select();
        $select
            ->from(
                ['main' => $this->table],
                $attributeCodes
            )
            ->join(
                ['entity_id' => $this->getMainMemoryTable('entity_id')],
                'entity_id.id = main.entity_id',
                []
            )
            ;


        $data = $this->_getReadAdapter()->fetchAssoc($select);

        return $data;
    }

}
