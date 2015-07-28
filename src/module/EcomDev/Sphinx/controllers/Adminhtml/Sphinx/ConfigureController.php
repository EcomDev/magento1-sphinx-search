<?php

class EcomDev_Sphinx_Adminhtml_Sphinx_ConfigureController
    extends EcomDev_Sphinx_Controller_Adminhtml
{
    protected $_menu = 'catalog/sphinx/configure';

    /**
     * Returns an instance of assortment model
     *
     * @return EcomDev_Sphinx_Model_Sphinx_Config
     */
    protected function _getModel()
    {
        return Mage::getModel('ecomdev_sphinx/sphinx_config');
    }

    /**
     * Initializes admin titles
     *
     * @param null|EcomDev_Sphinx_Model_AbstractModel $currentObject
     * @return string
     */
    protected function _initTitles(EcomDev_Sphinx_Model_AbstractModel $currentObject = null)
    {
        $this->_title($this->__('Catalog'))
            ->_title($this->__('Sphinx Search'))
            ->_title($this->__('Configure Sphinx'));
        
        if ($this->getRequest()->getActionName() === 'index') {
            try {
                if ($this->_getModel()->isManage()) {
                    if (!$this->_getModel()->isRunning()) {
                        $this->_getSession()->addWarning(
                            $this->__('Sphinx service is not running, consider running "Control Daemon" action')
                        );
                    }
                    if ($this->_getModel()->isDaemonConfigDifferent()) {
                        $this->_getSession()->addWarning(
                            $this->__('Sphinx service configuration is out of sync, consider running "Control Daemon" action')
                        );
                    }
                }

                if ($this->_getModel()->isIndexConfigDifferent()) {
                    $this->_getSession()->addWarning(
                        $this->__('Index configuration file is out of sync, consider running "Control Index" action')
                    );
                }
            } catch (Exception $e) {
                $this->_getSession()->addError(
                    $this->__('Cannot connect to a sphinx server for detecting its status, review settings in configuration')
                );
            }
            
            
        }
    }

    /**
     * Returns object title for error messages
     *
     * @return string
     */
    protected function _getObjectTitle()
    {
        // TODO: Implement _getObjectTitle() method.
    }
    
    
    public function controlDaemonAction()
    {
        $this->_manageAction('controlDaemon', $this->__('Daemon control operation has been successfully executed'));
    }

    public function controlIndexAction()
    {
        $this->_manageAction('controlIndex', $this->__('Index control operation has been successfully executed'));
    }

    public function indexDeltaAction()
    {
        $this->_manageAction('controlIndexData', $this->__('Delta update operation has been successfully executed'));
    }


    protected function _manageAction($method, $successText)
    {
        try {
            $this->_getModel()->$method();
            $this->_getSession()->addSuccess($successText);
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_getSession()->addError($e->getMessage());
        }
        
        $this->_redirect('*/*/');
    }
}
