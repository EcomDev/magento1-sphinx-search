<?php

use EcomDev_Sphinx_Contract_Reader_PluginInterface as PluginInterface;
use EcomDev_Sphinx_Contract_Reader_PluginContainerInterface as PluginContainerInterface;
use EcomDev_Sphinx_Contract_Reader_ScopeInterface as ScopeInterface;
use EcomDev_Sphinx_Contract_Reader_ProviderInterface as ProviderInterface;

/***
 * Reader interface
 *
 * Extended from iterator to support foreach
 *
 */
interface EcomDev_Sphinx_Contract_ReaderInterface extends Iterator
{
    const DEFAULT_BATCH_SIZE = 1000;

    /**
     * Returns plugin container
     *
     * @return PluginContainerInterface
     */
    public function getPluginContainer();

    /**
     * Returns provider instance
     *
     * @return ProviderInterface
     */
    public function getProvider();

    /**
     * Sets batch size for a reader
     *
     * @param int $size
     * @return $this
     */
    public function setBatchSize($size);

    /**
     * Sets scope for a definition
     *
     * @param ScopeInterface $scope
     * @return $this
     */
    public function setScope(ScopeInterface $scope);

    /**
     * Adds plugin interface
     *
     * @param PluginInterface $plugin
     * @param $priority
     * @return mixed
     */
    public function addPlugin(PluginInterface $plugin, $priority);

}
