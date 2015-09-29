<?php

use EcomDev_Sphinx_Contract_Reader_PluginInterface as PluginInterface;
use EcomDev_Sphinx_Contract_Reader_PluginContainerInterface as PluginContainerInterface;



/***
 * Reader interface
 *
 * Extended from iterator to support foreach
 *
 */
interface EcomDev_Sphinx_Contract_ReaderInterface extends Iterator
{
    /**
     * Returns plugin container
     *
     * @return PluginContainerInterface
     */
    public function getPluginContainer();

    /**
     * Sets batch size for a reader
     *
     * @param int $size
     * @return $this
     */
    public function setBatchSize($size);
}
