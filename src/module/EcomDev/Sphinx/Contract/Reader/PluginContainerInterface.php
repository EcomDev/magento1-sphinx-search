<?php

use EcomDev_Sphinx_Contract_Reader_PluginInterface as PluginInterface;
use EcomDev_Sphinx_Contract_Reader_ScopeInterface as ScopeInterface;


interface EcomDev_Sphinx_Contract_Reader_PluginContainerInterface
{
    /**
     * Registers plugin for reader
     *
     * @param PluginInterface $plugin
     * @param int $priority
     * @return $this
     */
    public function add(PluginInterface $plugin, $priority);

    /**
     * Checks if plugin is already added
     *
     * @param PluginInterface $plugin
     * @return bool
     */
    public function contains(PluginInterface $plugin);

    /**
     * Removes plugin from container
     *
     * @param PluginInterface $plugin
     * @return $this
     */
    public function remove(PluginInterface $plugin);

    /**
     * Returns all plugins added to reader
     *
     * @return PluginInterface[]
     */
    public function get();

    /**
     * Reads data and returns result per identifier
     *
     * @param int[] $identifiers
     * @param ScopeInterface $scope
     * @return mixed[][]
     */
    public function read(array $identifiers, ScopeInterface $scope);
}
