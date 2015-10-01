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
     * @param int[] $identifiers
     * @param ScopeInterface $scope
     * @return array[]
     */
    public function read(array $identifiers, ScopeInterface $scope);
}
