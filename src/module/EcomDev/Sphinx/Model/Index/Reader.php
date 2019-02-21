<?php

use EcomDev_Sphinx_Contract_Reader_ScopeInterface as ScopeInterface;
use EcomDev_Sphinx_Contract_Reader_PluginContainerInterface as PluginContainerInterface;
use EcomDev_Sphinx_Contract_Reader_ProviderInterface as ProviderInterface;
use EcomDev_Sphinx_Contract_Reader_PluginInterface as PluginInterface;
use EcomDev_Sphinx_Contract_DataRowFactoryInterface as DataRowFactoryInterface;
use EcomDev_Sphinx_Contract_DataRowInterface as DataRowInterface;
use EcomDev_Sphinx_Contract_Reader_SnapshotAwareInterface as SnapshotAwareInterface;

/**
 * Sphinx reader interface
 *
 */
class EcomDev_Sphinx_Model_Index_Reader
    implements EcomDev_Sphinx_Contract_ReaderInterface
{
    /**
     * Size of the batch
     *
     * @var int
     */
    private $batchSize = self::DEFAULT_BATCH_SIZE;

    /**
     * Maximum identifier in reader set
     *
     * @var int
     */
    private $maxIdentifier;

    /**
     * Minimum identifier in reader set
     *
     * @var int
     */
    private $minIdentifier;

    /**
     * Scope interface for reader
     *
     * @var ScopeInterface
     */
    private $scope;

    /**
     * @var PluginContainerInterface
     */
    private $pluginContainer;

    /**
     * Provider of the data
     *
     * @var ProviderInterface
     */
    private $provider;

    /**
     * Last identifier
     *
     * @var int
     */
    private $nextIdentifier;

    /**
     * Data row instance
     *
     * @var DataRowInterface
     */
    private $dataRow;

    /**
     * Current identifiers
     *
     * @var DataRowFactoryInterface
     */
    private $dataRowFactory;

    /**
     * Constructor with dependencies
     *
     */
    public function __construct(
        PluginContainerInterface $pluginContainer,
        DataRowFactoryInterface $dataRowFactory,
        ProviderInterface $provider
    )
    {
        $this->pluginContainer = $pluginContainer;
        $this->provider = $provider;
        $this->dataRowFactory = $dataRowFactory;

        if ($this->provider instanceof EcomDev_Sphinx_Contract_Reader_Provider_PluginContainerAwareInterface) {
            $this->provider->setPluginContainer($this->pluginContainer);
        }
    }


    /**
     * Returns data row instance
     *
     * @return DataRowInterface
     */
    public function current()
    {
        return $this->dataRow;
    }

    /**
     * Returns the next batch if row processing is finished
     */
    public function next()
    {
        if ($this->dataRow === null) {
            return;
        }

        if (!$this->dataRow->next() && !$this->loadBatch()) {
            $this->reset();
        }
    }

    /**
     * Returns data row identifier
     *
     * @return int
     */
    public function key()
    {
        if ($this->dataRow === null) {
            return false;
        }

        return $this->dataRow->getId();
    }

    /**
     * Returns true if data row is loaded
     *
     * @return bool
     */
    public function valid()
    {
        return $this->dataRow !== null;
    }

    /**
     * Initializes provider
     *
     * @return void
     */
    public function rewind()
    {
        if ($this->scope === null) {
            return;
        }

        list($this->minIdentifier, $this->maxIdentifier) = $this->provider->getLimit($this->scope);

        if (!$this->loadBatch()) {
            $this->reset();
        }
    }

    /**
     * Loads data batch
     *
     * @return $this
     */
    private function loadBatch()
    {
        if ($this->maxIdentifier === null || $this->minIdentifier === null) {
            return false;
        }

        if ($this->nextIdentifier === null) {
            $this->nextIdentifier = $this->minIdentifier;
        }

        if ($this->maxIdentifier < $this->nextIdentifier) {
            return false;
        }

        unset($this->dataRow);

        $rows = $this->provider->getRows($this->scope, $this->nextIdentifier, $this->maxIdentifier, $this->batchSize);
        $entityIdentifiers = array_keys($rows);

        $snapshot = null;
        if ($this->provider instanceof SnapshotAwareInterface) {
            $snapshot = $this->provider->getSnapshot();
        }

        if ($snapshot && $entityIdentifiers) {
            $snapshot->createSnapshot(
                $this->scope
            );
        }

        if ($entityIdentifiers) {
            $this->nextIdentifier = end($entityIdentifiers) + 1;
            $additionalData = $this->pluginContainer->read($entityIdentifiers, $this->scope);
            $this->dataRow = $this->dataRowFactory->createDataRow($rows, $additionalData);
        }

        if ($snapshot) {
            $snapshot->destroySnapshot();
        }

        if ($this->provider instanceof EcomDev_Sphinx_Model_Resource_Index_Reader_CleanUpInterface) {
            $this->provider->cleanUp();
        }

        // In case there is no more data for batch we returns nothing
        return !empty($entityIdentifiers);
    }

    /**
     * Returns plugin container
     *
     * @return PluginContainerInterface
     */
    public function getPluginContainer()
    {
        return $this->pluginContainer;
    }

    /**
     * Sets batch size for a reader
     *
     * @param int $size
     * @return $this
     */
    public function setBatchSize($size)
    {
        $this->batchSize = $size;
        return $this;
    }

    /**
     * Sets scope for a definition
     *
     * @param ScopeInterface $scope
     * @return $this
     */
    public function setScope(ScopeInterface $scope)
    {
        $this->scope = $scope;
        $this->reset();
        return $this;
    }

    /**
     * Resets iterator
     *
     * @return $this
     */
    private function reset()
    {
        $this->maxIdentifier = null;
        $this->minIdentifier = null;
        $this->dataRow = null;
        return $this;
    }

    /**
     * Adds plugin interface
     *
     * @param PluginInterface $plugin
     * @param int $priority
     * @return $this
     */
    public function addPlugin(PluginInterface $plugin, $priority)
    {
        $this->getPluginContainer()->add($plugin, $priority);
        return $this;
    }

    /**
     * Returns provider instance
     *
     * @return ProviderInterface
     */
    public function getProvider()
    {
        return $this->provider;
    }
}
