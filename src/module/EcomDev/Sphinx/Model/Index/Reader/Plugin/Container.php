<?php

use EcomDev_Sphinx_Contract_Reader_ScopeInterface as ScopeInterface;
use EcomDev_Sphinx_Contract_Reader_PluginInterface as PluginInterface;

class EcomDev_Sphinx_Model_Index_Reader_Plugin_Container
    implements EcomDev_Sphinx_Contract_Reader_PluginContainerInterface
{
    /**
     * Plugin interface
     *
     * @var PluginInterface[]
     */
    private $plugins = [];

    /**
     * Sort order of plugins
     *
     * @var string[]
     */
    private $pluginSort = [];

    /**
     * Registers plugin for reader
     *
     * @param PluginInterface $plugin
     * @param int $priority
     * @return $this
     */
    public function add(PluginInterface $plugin, $priority)
    {
        $pluginId = $this->getPluginId($plugin);
        $this->plugins[$pluginId] = $plugin;
        $this->pluginSort[$pluginId] = $priority;

        asort($this->pluginSort);
        return $this;
    }

    /**
     * Returns plugin identifier
     *
     * @param PluginInterface $plugin
     * @return string
     */
    private function getPluginId(PluginInterface $plugin)
    {
        return spl_object_hash($plugin);
    }

    /**
     * Checks if plugin is already added
     *
     * @param PluginInterface $plugin
     * @return bool
     */
    public function contains(PluginInterface $plugin)
    {
        return isset($this->plugins[$this->getPluginId($plugin)]);
    }

    /**
     * Removes plugin from container
     *
     * @param PluginInterface $plugin
     * @return $this
     */
    public function remove(PluginInterface $plugin)
    {
        if ($this->contains($plugin)) {
            $pluginId = $this->getPluginId($plugin);
            unset($this->plugins[$pluginId]);
            unset($this->pluginSort[$pluginId]);
        }

        return $this;
    }

    /**
     * Returns all plugins added to reader
     *
     * @return PluginInterface[]
     */
    public function get()
    {
        $result = [];
        foreach ($this->pluginSort as $pluginId => $order) {
            $result[] = $this->plugins[$pluginId];
        }

        return $result;
    }

    /**
     * Reads data and returns result per identifier
     *
     * @param int[] $identifiers
     * @param ScopeInterface $scope
     * @return mixed
     */
    public function read(array $identifiers, ScopeInterface $scope)
    {
        $result = [];

        foreach (array_reverse($this->pluginSort) as $pluginId => $order) {
            $data = $this->plugins[$pluginId]->read($identifiers, $scope);

            if (!$data) {
                continue;
            }

            // Smart and fast merge process
            foreach ($data as $identifier => $row) {
                if (!isset($result[$identifier])) {
                    $result[$identifier] = [];
                }

                $result[$identifier] += $row;
            }
        }

        return $result;
    }

}
