<?php

use EcomDev_Sphinx_Contract_Reader_ScopeInterface as ScopeInterface;
use Mage_Catalog_Model_Product_Visibility as Visibility;


class EcomDev_Sphinx_Model_Resource_Index_Reader_Provider_Product
    extends EcomDev_Sphinx_Model_Resource_Index_Reader_Provider_AbstractProvider
{

    /**
     * Resource initialization
     */
    protected function _construct()
    {
        $this->_init('ecomdev_sphinx/index_product', 'product_id');
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
        $select->from(['index' => $this->getMainTable()], ['product_id', 'status', 'visibility']);

        foreach ($scope->getFilters() as $filter) {
            $filter->render('index', $select);
        }

        $select->where('index.product_id >= :start');
        $select->where('index.product_id <= :end');

        $multiply = 1;

        $totalSelect = clone $select;
        $totalSelect->reset(Varien_Db_Select::COLUMNS);
        $totalSelect->columns('COUNT(product_id)');

        do {
            $lastIdentifier = $nextIdentifier + ($batchSize * $multiply);
            $currentSize = $this->_getReadAdapter()->fetchOne(
                $totalSelect,
                [':start' => $nextIdentifier, ':end' => min($maximumIdentifier, $lastIdentifier)]
            );
            $multiply += 1;
        } while ($currentSize < $batchSize && $lastIdentifier <= $maximumIdentifier);

        $select->order('product_id ASC');
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
     * It is always a product
     *
     * @return string
     */
    protected function _getType()
    {
        return 'product';
    }

    /**
     * Returns type of meta record
     *
     * @param ScopeInterface $scope
     * @return string
     */
    protected function _getMetaType(ScopeInterface $scope)
    {
        if ($scope->hasFilter('visibility')) {
            $visibilities = $scope->getFilter('visibility')->getValue();

            foreach ($visibilities as $visibility) {
                if ($visibility == Visibility::VISIBILITY_IN_CATALOG) {
                    return $this->_getType() . '_catalog';
                } elseif ($visibility == Visibility::VISIBILITY_IN_SEARCH) {
                    return $this->_getType() . '_search';
                }
            }

            return $this->_getType() . '_invisible';
        }

        return parent::_getMetaType($scope);
    }


}
