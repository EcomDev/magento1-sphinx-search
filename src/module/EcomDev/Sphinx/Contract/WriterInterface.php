<?php

use EcomDev_Sphinx_Contract_ReaderInterface as ReaderInterface;
use EcomDev_Sphinx_Contract_Reader_ScopeInterface as ScopeInterface;

/**
 * Returns stream output
 *
 */
interface EcomDev_Sphinx_Contract_WriterInterface
{
    /**
     * Stream where data gets written
     *
     * @return resource
     */
    public function getStream();

    /**
     * Processes reader within specified scope
     *
     * @param ReaderInterface $reader
     * @param ScopeInterface $scope
     * @return $this
     */
    public function process(ReaderInterface $reader, ScopeInterface $scope);
}
