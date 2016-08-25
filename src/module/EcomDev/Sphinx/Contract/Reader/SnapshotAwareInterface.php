<?php

use EcomDev_Sphinx_Contract_Reader_SnapshotInterface as SnapshotInterface;

/**
 * Snapshot aware interface
 *
 */
interface EcomDev_Sphinx_Contract_Reader_SnapshotAwareInterface
{
    /**
     * Sets a snapshot to a model
     *
     * @param SnapshotInterface $snapshot
     *
     * @return $this
     */
    public function setSnapshot(SnapshotInterface $snapshot);

    /**
     * Returns snapshot instance
     *
     * @return SnapshotInterface
     */
    public function getSnapshot();
}
