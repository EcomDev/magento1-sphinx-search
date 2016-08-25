<?php

interface EcomDev_Sphinx_Contract_Reader_SnapshotInterface
    extends EcomDev_Sphinx_Model_Resource_Index_Reader_Plugin_MemoryTableAwareInterface
{
    /**
     * Builds snapshot of attribute data
     *
     * @param EcomDev_Sphinx_Contract_Reader_ScopeInterface $scope
     *
     * @return $this
     */
    public function createSnapshot(
        EcomDev_Sphinx_Contract_Reader_ScopeInterface $scope
    );

    /**
     * Returns attribute type table build by snapshot
     *
     * @param string $attributeType
     *
     * @return string
     */
    public function getSnapshotTable($attributeType);

    /**
     * Destroys created snapshot tables
     *
     * @return mixed
     */
    public function destroySnapshot();
}
