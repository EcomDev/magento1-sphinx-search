<?php

/**
 * Category indexer for sphinx
 * 
 * 
 */
class EcomDev_Sphinx_Model_Indexer_Catalog_Category
    extends Mage_Index_Model_Indexer_Abstract
{
    const EVENT_MATCH_RESULT_KEY = 'sphinx_catalog_category_match_result';
    const EVENT_MATCH_SKIP_KEY = 'sphinx_catalog_category_skip';
    const EVENT_CATEGORY_ID = 'sphinx_catalog_category_id';
    const EVENT_CATEGORY_IDS = 'sphinx_catalog_category_ids';

    /**
     * Matched entity events
     *
     * @var array
     */
    protected $_matchedEntities = array(
        Mage_Catalog_Model_Category::ENTITY => array(
            Mage_Index_Model_Event::TYPE_SAVE
        ),
        Mage_Core_Model_Store::ENTITY => array(
            Mage_Index_Model_Event::TYPE_SAVE
        ),
        Mage_Core_Model_Store_Group::ENTITY => array(
            Mage_Index_Model_Event::TYPE_SAVE
        ),
    );

    /**
     * Initializes a resource model
     * 
     */
    protected function _construct()
    {
        $this->_init('ecomdev_sphinx/indexer_catalog_category');
    }
    
    /**
     * Retrieve Indexer name
     *
     * @return string
     */
    public function getName()
    {
        return Mage::helper('ecomdev_sphinx')->__('Sphinx Category');
    }

    /**
     * Retrieve Indexer description
     *
     * @return string
     */
    public function getDescription()
    {
        return Mage::helper('ecomdev_sphinx')->__('Prepares index for a category sphinx navigation');
    }

    /**
     * Check if event can be matched by process
     * Overwrote for check is flat catalog category is enabled and specific save
     * category, store, store_group
     *
     * @param Mage_Index_Model_Event $event
     * @return bool
     */
    public function matchEvent(Mage_Index_Model_Event $event)
    {
        $data = $event->getNewData();
        if (isset($data[self::EVENT_MATCH_RESULT_KEY])) {
            return $data[self::EVENT_MATCH_RESULT_KEY];
        }

        $entity = $event->getEntity();
        if ($entity == Mage_Core_Model_Store::ENTITY) {
            if ($event->getType() == Mage_Index_Model_Event::TYPE_DELETE) {
                $result = true;
            } elseif ($event->getType() == Mage_Index_Model_Event::TYPE_SAVE) {
                /** @var $store Mage_Core_Model_Store */
                $store = $event->getDataObject();
                if ($store && ($store->isObjectNew()
                        || $store->dataHasChangedFor('group_id')
                        || $store->dataHasChangedFor('root_category_id')
                    )) {
                    $result = true;
                } else {
                    $result = false;
                }
            } else {
                $result = false;
            }
        } elseif ($entity == Mage_Core_Model_Store_Group::ENTITY) {
            /** @var $storeGroup Mage_Core_Model_Store_Group */
            $storeGroup = $event->getDataObject();
            if ($storeGroup
                && ($storeGroup->dataHasChangedFor('website_id') || $storeGroup->dataHasChangedFor('root_category_id'))
            ) {
                $result = true;
            } else {
                $result = false;
            }
        } else {
            $result = parent::matchEvent($event);
        }
        
        return $result;
    }

    /**
     * Register data required by process in event object
     *
     * @param Mage_Index_Model_Event $event
     */
    protected function _registerEvent(Mage_Index_Model_Event $event)
    {
        $event->addNewData(self::EVENT_MATCH_RESULT_KEY, true);
        switch ($event->getEntity()) {
            case Mage_Catalog_Model_Category::ENTITY:
                /* @var $category Mage_Catalog_Model_Category */
                $category = $event->getDataObject();

                /**
                 * Check if category has another affected category ids (category move result)
                 */
                $affectedCategoryIds = $category->getAffectedCategoryIds();
                
                if ($affectedCategoryIds) {
                    $event->addNewData(self::EVENT_CATEGORY_IDS, $affectedCategoryIds);
                } else {
                    $event->addNewData(self::EVENT_CATEGORY_ID, $category->getId());
                }
                break;

            case Mage_Core_Model_Store::ENTITY:
            case Mage_Core_Model_Store_Group::ENTITY:
                $event->addNewData(self::EVENT_MATCH_SKIP_KEY, true);
                $process = $event->getProcess();
                $process->changeStatus(Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX);
                break;
        }
    }

    /**
     * Process event
     *
     * @param Mage_Index_Model_Event $event
     */
    protected function _processEvent(Mage_Index_Model_Event $event)
    {
        $data = $event->getNewData();

        if (!empty($data[self::EVENT_MATCH_RESULT_KEY]) && empty($data[self::EVENT_MATCH_SKIP_KEY])) {
            $this->callEventHandler($event);
        }
    }
}
