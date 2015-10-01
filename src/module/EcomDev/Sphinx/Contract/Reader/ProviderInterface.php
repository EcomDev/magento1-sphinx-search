<?php

use EcomDev_Sphinx_Contract_Reader_ScopeInterface as ScopeInterface;

interface EcomDev_Sphinx_Contract_Reader_ProviderInterface
{
    /**
     * Returns limit of rows for current scope
     *
     * @param EcomDev_Sphinx_Contract_Reader_ScopeInterface $scope
     * @return int[]
     */
    public function getLimit(ScopeInterface $scope);

    /**
     * Returns rows from database with array key containing entity identifier
     *
     * @param ScopeInterface $scope
     * @param int $nextIdentifier
     * @param int $maximumIdentifier
     * @param int $batchSize
     * @return string[][]
     */
    public function getRows(ScopeInterface $scope, $nextIdentifier, $maximumIdentifier, $batchSize);

    /**
     * Returns kill records for product
     *
     * @param ScopeInterface $scope
     * @return mixed
     */
    public function getKillRecords(ScopeInterface $scope);

    /**
     * Returns meta type
     *
     * @param ScopeInterface $scopeInterface
     * @return string
     */
    public function getMetaType(ScopeInterface $scopeInterface);
}
