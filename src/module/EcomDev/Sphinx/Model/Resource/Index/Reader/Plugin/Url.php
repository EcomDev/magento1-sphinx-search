<?php

use EcomDev_Sphinx_Contract_Reader_ScopeInterface as ScopeInterface;

/**
 * Stock index data retriever
 *
 *
 */
class EcomDev_Sphinx_Model_Resource_Index_Reader_Plugin_Url
    extends EcomDev_Sphinx_Model_Resource_Index_Reader_Plugin_AbstractPlugin
{
    /**
     * Stock identifier
     *
     * @var int
     */
    private $idPathFormat;

    /**
     * Based on entity type a table is found
     *
     * @param string $idPathFormat
     */
    public function __construct($idPathFormat)
    {
        parent::__construct();
        $this->idPathFormat = $idPathFormat;
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

        $idPathList = array_map(
            function ($identifier) {
                return sprintf($this->idPathFormat, $identifier);
            },
            $identifiers
        );

        $idPathMap = array_combine($idPathList, $identifiers);

        $select = $this->_getReadAdapter()->select();
        $select
            ->from(
                ['index' => $this->getTable('core/url_rewrite')],
                ['id_path', 'request_path']
            );

        $scope->getFilter('store_id')->render('index', $select);

        $select->where('index.is_system = ?', 1);
        $select->where('index.id_path IN(?)', $idPathList);

        $data = [];
        foreach ($this->_getReadAdapter()->query($select) as $row) {
            $data[$idPathMap[$row['id_path']]]['request_path'] = $row['request_path'];
        }
        return $data;
    }
}
