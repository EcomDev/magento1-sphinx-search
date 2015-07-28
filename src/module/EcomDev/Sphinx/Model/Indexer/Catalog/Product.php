<?php

class EcomDev_Sphinx_Model_Indexer_Catalog_Product
    extends Mage_Index_Model_Indexer_Abstract
{
    const EVENT_MATCH_RESULT_KEY = 'sphinx_catalog_product_match_result';
    const EVENT_MATCH_SKIP_KEY = 'sphinx_catalog_product_skip';
    const EVENT_PRODUCT_ID = 'sphinx_catalog_product_id';
    const EVENT_PRODUCT_IDS = 'sphinx_catalog_product_ids';

    /**
     * Matched entity events
     *
     * @var array
     */
    protected $_matchedEntities = array(
        Mage_Catalog_Model_Product::ENTITY => array(
            Mage_Index_Model_Event::TYPE_SAVE,
            Mage_Index_Model_Event::TYPE_MASS_ACTION
        ),
        Mage_Core_Model_Store::ENTITY => array(
            Mage_Index_Model_Event::TYPE_SAVE
        ),
        Mage_Core_Model_Store_Group::ENTITY => array(
            Mage_Index_Model_Event::TYPE_SAVE
        ),
        Mage_Catalog_Model_Convert_Adapter_Product::ENTITY => array(
            Mage_Index_Model_Event::TYPE_SAVE
        ),
        EcomDev_Sphinx_Model_Attribute::ENTITY => array(
            Mage_Index_Model_Event::TYPE_SAVE,
            Mage_Index_Model_Event::TYPE_DELETE
        ),
        Mage_Customer_Model_Group::ENTITY => array(
            Mage_Index_Model_Event::TYPE_SAVE
        )
    );

    /**
     * Initializes a resource model
     * 
     */
    protected function _construct()
    {
        $this->_init('ecomdev_sphinx/indexer_catalog_product');
    }
    
    /**
     * Retrieve Indexer name
     *
     * @return string
     */
    public function getName()
    {
        return Mage::helper('ecomdev_sphinx')->__('Sphinx Products');
    }

    /**
     * Retrieve Indexer description
     *
     * @return string
     */
    public function getDescription()
    {
        return Mage::helper('ecomdev_sphinx')->__('Prepares index for sphinx search daemon');
    }

    /**
     * Returns a config model instance
     * 
     * @return EcomDev_Sphinx_Model_Config
     */
    public function getConfig()
    {
        return Mage::getSingleton('ecomdev_sphinx/config');
    }
    
    /**
     * Check if event can be matched by process, overridden to change index status
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
        if ($entity == EcomDev_Sphinx_Model_Attribute::ENTITY) {
            if ($event->getType() == Mage_Index_Model_Event::TYPE_DELETE) {
                $result = true;
            } else {
                /* @var $attribute Mage_Core_Model_Store */
                $attribute = $event->getDataObject();
                if ($attribute && $attribute->isObjectNew()) {
                    $result = true;
                } else {
                    $result = false;
                }
            }
        } elseif ($entity == Mage_Core_Model_Store::ENTITY) {
            /* @var $store Mage_Core_Model_Store */
            $store = $event->getDataObject();
            if ($store && ($store->isObjectNew() || $store->dataHasChangedFor('website_id'))) {
                $result = true;
            } else {
                $result = false;
            }
        } elseif ($entity == Mage_Core_Model_Store_Group::ENTITY) {
            /* @var $storeGroup Mage_Core_Model_Store_Group */
            $storeGroup = $event->getDataObject();
            if ($storeGroup && $storeGroup->dataHasChangedFor('website_id')) {
                $result = true;
            } else {
                $result = false;
            }
        } elseif ($event->getEntity() == Mage_Customer_Model_Group::ENTITY) {
            $result = $event->getDataObject() && $event->getDataObject()->isObjectNew();
        } else {
            $result = parent::matchEvent($event);
        }

        $event->addNewData(self::EVENT_MATCH_RESULT_KEY, $result);

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
            case Mage_Catalog_Model_Product::ENTITY:
                $this->_registerCatalogProductEvent($event);
                break;
            
            case Mage_Catalog_Model_Convert_Adapter_Product::ENTITY:
            case Mage_Core_Model_Store::ENTITY:
            case Mage_Core_Model_Store_Group::ENTITY:
            case Mage_Customer_Model_Group::ENTITY:
            case EcomDev_Sphinx_Model_Attribute::ENTITY:
                $this->_changeStatus($event);
                break;
        }
    }

    /**
     * Register data required by catalog product process in event object
     *
     * @param Mage_Index_Model_Event $event
     * @return Mage_Catalog_Model_Product_Indexer_Flat
     */
    protected function _registerCatalogProductEvent(Mage_Index_Model_Event $event)
    {
        switch ($event->getType()) {
            case Mage_Index_Model_Event::TYPE_SAVE:
                /* @var $product Mage_Catalog_Model_Product */
                $product = $event->getDataObject();
                $event->addNewData(self::EVENT_PRODUCT_ID, $product->getId());
                break;

            case Mage_Index_Model_Event::TYPE_MASS_ACTION:
                /* @var $actionObject Varien_Object */
                $actionObject = $event->getDataObject();
                
                $updatedAttributes = array_keys($actionObject->getAttributesData());
                $usedAttributeCodes = $this->getConfig()->getUsedAttributeCodes();
                
                if (is_array($actionObject->getProductIds()) 
                    && array_intersect($usedAttributeCodes, $updatedAttributes)) {
                    $event->addNewData(self::EVENT_PRODUCT_IDS, $actionObject->getProductIds());
                }
                break;
        }

        return $this;
    }

    /**
     * Changes the status for product sphinx index
     * 
     * @param Mage_Index_Model_Event $event
     * @return $this
     */
    protected function _changeStatus(Mage_Index_Model_Event $event)
    {
        $event->addNewData(self::EVENT_MATCH_SKIP_KEY, true);
        $process = $event->getProcess();
        $process->changeStatus(Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX);
        return $this;
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
