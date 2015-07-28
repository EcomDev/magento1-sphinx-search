<?php

class EcomDev_Sphinx_Model_Indexer_Dummy extends Mage_Index_Model_Indexer_Abstract
{
    /**
     * Whether the indexer should be displayed on process/list page
     *
     * @return bool
     */
    public function isVisible()
    {
        return false;
    }

    /**
     * Get Indexer name
     *
     * @return string
     */
    public function getName()
    {
        return 'Dummy Indexer';
    }

    /**
     * Get indexer description
     * 
     * @return string
     */
    public function getDescription()
    {
        return "Dummy Indexer, you shouldn't see this";
    }
    
    /**
     * Register indexer required data inside event object
     *
     * @param   Mage_Index_Model_Event $event
     * @return  $this
     */
    protected function _registerEvent(Mage_Index_Model_Event $event)
    {
        return $this;
    }

    /**
     * Process event based on event state data
     *
     * @param   Mage_Index_Model_Event $event
     * @return  $this
     */
    protected function _processEvent(Mage_Index_Model_Event $event)
    {
        return $this;
    }
}
