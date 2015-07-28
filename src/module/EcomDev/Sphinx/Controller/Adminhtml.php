<?php


abstract class EcomDev_Sphinx_Controller_Adminhtml
    extends Mage_Adminhtml_Controller_Action
{
    protected $_prefix = 'object';
    protected $_idField = 'object_id';
    protected $_menu = '';

    /**
     * Initialize brand model from request
     *
     * @return EcomDev_Sphinx_Model_AbstractModel
     */
    protected function _initObject()
    {
        $object = $this->_getModel();

        $id = $this->getRequest()->getParam($this->_idField, false);
        
        if ($id !== false && ($loadResult = $this->_loadObject($object, $id)) !== true) {
            if (!is_array($loadResult)) {
                $loadResult = array($this->__('%s with specified id no longer exists', $this->_getObjectTitle()));
            }
            
            $this->_notifyErrors($loadResult, array(), '*/*/');
            return false;
        }
        
        if (count($this->_getSession()->getMessages()->getErrors())) {
            $objectData = $this->_getSession()->getData($this->_prefix . '_data', true);
            if (!empty($objectData)) {
                $object->addData($objectData);
            }
        }

        Mage::register('current_object', $object);
        return $object;
    }

    /**
     * Loads object for a controller, returns true if load operation worked
     * 
     * @param EcomDev_Sphinx_Model_AbstractModel $object
     * @param $id
     * @return bool
     */
    protected function _loadObject(EcomDev_Sphinx_Model_AbstractModel $object, $id)
    {
        $object->load($id);
        return $object->getId() !== null;
    }

    /**
     * Should return an instance of abstract model
     * 
     * @return EcomDev_Sphinx_Model_AbstractModel
     */
    abstract protected function _getModel();

    /**
     * Notifies user about the errors
     * 
     * @param $errors
     * @param array $storedData
     * @param string $path
     * @return $this
     */
    protected function _notifyErrors($errors, array $storedData = array(), $path = '*/*/edit')
    {
        foreach ($errors as $error) {
            $this->_getSession()->addError($error);
        }

        if ($storedData) {
            $this->_getSession()->setData(
                $this->_prefix . '_data',
                $storedData
            );
        }
        
        $params = array(
            '_current' => true
        );
        
        if ($activeTab = $this->getRequest()->getPost('active_tab')) {
            $params['active_tab'] = $activeTab;
        }

        $this->_redirect($path, $params);
        return $this;
    }

    /**
     * Initializes admin titles
     *
     * @param null|EcomDev_Sphinx_Model_AbstractModel $currentObject
     * @return string
     */
    abstract protected function _initTitles(EcomDev_Sphinx_Model_AbstractModel $currentObject = null);

    /**
     * Returns object title for error messages
     *
     * @return string
     */
    abstract protected function _getObjectTitle();

    /**
     * Manage objects grid ajax action
     *
     */
    public function gridAction()
    {
        $this->loadLayout(false);
        $this->renderLayout();
    }

    /**
     * New object action
     *
     */
    public function newAction()
    {
        $this->_forward('edit');
    }

    /**
     * Manage Assortment action
     *
     */
    public function indexAction()
    {
        $this->_initTitles();
        $this->loadLayout();
        $this->_setActiveMenu($this->_menu);
        $this->renderLayout();
    }

    /**
     * Edit brand page
     *
     */
    public function editAction()
    {
        $object = $this->_initObject();
        if (!$object) {
            return;
        }

        $this->_initTitles($object);

        $this->loadLayout();
        $this->_setActiveMenu($this->_menu);
        $this->renderLayout();
    }

    /**
     * Delete brand action
     *
     */
    public function deleteAction()
    {

        if ($object = $this->_initObject()) {
            try {
                $object->delete();
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    $this->__('%s was successfully deleted', $this->_getObjectTitle())
                );
            }
            catch (Mage_Core_Exception $e){
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->getResponse()->setRedirect($this->getUrl('*/*/edit', array('_current'=>true)));
                return;
            }
            catch (Exception $e){
                Mage::logException($e);
                Mage::getSingleton('adminhtml/session')->addError(
                    $this->__('%s deleting error', $this->_getObjectTitle())
                );
                $this->getResponse()->setRedirect($this->getUrl('*/*/edit', array('_current'=>true)));
                return;
            }
        }
        $this->getResponse()->setRedirect($this->getUrl('*/*/', array('_current'=>true, 'id'=>null)));
    }

    /**
     * Save brand action
     *
     */
    public function saveAction()
    {
        $object = $this->_initObject();

        if (!$object) {
            return;
        }

        $postData = $this->getRequest()->getPost($this->_prefix);

        if (!is_array($postData)) {
            $postData = array();
        }

        $hookData = $this->_beforeSetData($object, $postData);

        if (is_array($hookData)) {
            $postData = $hookData;
        }

        $object->setDataFromArray($postData);

        $this->_afterSetData($object);

        $result = $object->validate();

        $errors = array();
        
        if ($result !== true) {
            $errors = array_merge($errors, $result);
        }

        if (empty($errors)) { // Try to save brand if no validation errors apeared
            try {
                $object->save();
                $this->_getSession()->addSuccess(
                    $this->__('%s was successfully saved', $this->_getObjectTitle())
                );
            } catch (Mage_Core_Exception $e) {
                $error[] = $e->getMessage();
            } catch (Exception $e) {
                Mage::logException($e);
                $error[] = $this->__('%s saving problem', $this->_getObjectTitle());
            }
        }

        if (!empty($errors)) {
            return $this->_notifyErrors($errors, $object->toArray());
        }

        if ($this->getRequest()->getParam('continue')) {
            $this->_redirect('*/*/edit', array(
                '_current' => true,
                $this->_idField => $object->getId()
            ));
        } else {
            $this->_redirect('*/*/');
        }
    }

    /**
     * Hook on before set data for object
     *
     * @param EcomDev_Sphinx_Model_AbstractModel $object
     * @param array $postData
     * @return $this
     */
    protected function _beforeSetData(EcomDev_Sphinx_Model_AbstractModel $object, $postData)
    {
        return $this;
    }

    /**
     * Hook on after set data for object
     *
     * @param EcomDev_Sphinx_Model_AbstractModel $object
     * @return $this
     */
    protected function _afterSetData(EcomDev_Sphinx_Model_AbstractModel $object)
    {
        return $this;
    }

    /**
     * Checks access to resource
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed($this->_menu);
    }
}
