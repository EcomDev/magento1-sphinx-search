<?php

use EcomDev_Sphinx_Contract_Reader_ScopeInterface as ScopeInterface;
use Mage_Catalog_Model_Product_Visibility as Visibility;


class EcomDev_Sphinx_Model_Resource_Index_Reader_Provider_Category
    extends EcomDev_Sphinx_Model_Resource_Index_Reader_Provider_AbstractProvider
{

    /**
     * Resource initialization
     */
    protected function _construct()
    {
        $this->_init('ecomdev_sphinx/index_category', 'category_id');
    }


    /**
     * Returns rows from database with array key containing entity identifier
     *
     * @param ScopeInterface $scope
     * @param int $nextIdentifier
     * @param int $maximumIdentifier
     * @param int $batchSize
     * @return string[][]
     */
    public function getRows(ScopeInterface $scope, $nextIdentifier, $maximumIdentifier, $batchSize)
    {
        if (!$scope->hasFilter('store_id')) {
            return [];
        }

        $this->_validateConnection($this->_getReadAdapter());

        $select = $this->_getReadAdapter()->select();
        $select->from(
            ['index' => $this->getMainTable()],
            ['category_id', 'path', 'is_active', 'position', 'level']
        );

        foreach ($scope->getFilters() as $filter) {
            $filter->render('index', $select);
        }

        $select->where('index.category_id >= :start');
        $select->where('index.category_id <= :end');

        $multiply = 1;

        $totalSelect = clone $select;
        $totalSelect->reset(Varien_Db_Select::COLUMNS);
        $totalSelect->columns('COUNT(category_id)');

        do {
            $lastIdentifier = $nextIdentifier + ($batchSize * $multiply);
            $currentSize = $this->_getReadAdapter()->fetchOne(
                $totalSelect,
                [':start' => $nextIdentifier, ':end' => min($maximumIdentifier, $lastIdentifier)]
            );
            $multiply += 1;
        } while ($currentSize < $batchSize && $lastIdentifier <= $maximumIdentifier);

        $select->order('category_id ASC');
        $select->limit($batchSize);

        $data = $this->_getReadAdapter()->fetchAssoc(
            $select,
            [':start' => $nextIdentifier, ':end' => min($maximumIdentifier, $lastIdentifier)]
        );

        if ($lastIdentifier >= $maximumIdentifier) {
            $this->endProcess($scope);
        }

        return $data;
    }

    /**
     * It is always category
     *
     * @return string
     */
    protected function _getType()
    {
        return 'category';
    }
}
