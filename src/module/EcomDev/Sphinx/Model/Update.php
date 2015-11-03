<?php

class EcomDev_Sphinx_Model_Update extends Mage_Core_Model_Abstract
{
    const ENTITY = 'sphinx_update';

    protected function _construct()
    {
        $this->_init('ecomdev_sphinx/update');
    }

    /**
     * Notify update by type
     *
     * @param string $type
     * @return $this
     */
    public function notify($type)
    {
        $indexProcessor = Mage::getSingleton('index/indexer')->getProcessByCode('catalog_category_product');
        if ($type === 'product'
            && $indexProcessor && $indexProcessor->isLocked()) {
            return $this;
        }

        $updatedAfter = $this->_getResource()->getLatestUpdatedAt($type);

        $this->getResource()->walkUpdatedEntityIds(
            $updatedAfter,
            $type,
            function ($entityIds, $type) {
                Mage::getSingleton('index/indexer')->processEntityAction(
                    new Varien_Object(['type' => $type, 'entity_ids' => $entityIds]),
                    self::ENTITY,
                    Mage_Index_Model_Event::TYPE_MASS_ACTION
                );
            },
            500
        );

        return $this;
    }
}
