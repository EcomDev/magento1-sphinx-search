<?php

use EcomDev_Sphinx_Contract_Reader_ScopeInterface as ScopeInterface;

/**
 * Data plugin interface
 *
 */
interface EcomDev_Sphinx_Contract_Reader_PluginInterface
{

    /**
     * Returns array of data per entity identifier
     *
     * @param int[] $entityIds
     * @param ScopeInterface $scope
     * @return array[]
     */
    public function read($entityIds, ScopeInterface $scope);
}
