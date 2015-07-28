<?php

class EcomDev_Sphinx_Adminhtml_Sphinx_ScopeController
    extends EcomDev_Sphinx_Controller_Adminhtml
{
    protected $_prefix = 'sphinx_scope';
    protected $_idField = 'scope_id';
    protected $_menu = 'catalog/sphinx/scope';

    /**
     * Returns an instance of assortment model
     *
     * @return EcomDev_Sphinx_Model_Scope
     */
    protected function _getModel()
    {
        return Mage::getModel('ecomdev_sphinx/scope');
    }
    
    /**
     * Initializes admin titles
     *
     * @param null|EcomDev_Sphinx_Model_Attribute $currentObject
     * @return string
     */
    protected function _initTitles(EcomDev_Sphinx_Model_AbstractModel $currentObject = null)
    {
        $this->_title($this->__('Catalog'))
            ->_title($this->__('Sphinx Search'))
            ->_title($this->__('Manage Scopes'));

        if ($currentObject !== null) {
            $this->_title(
                $currentObject->getId() ?
                    $this->__('Edit Sphinx Scope "%s"', $currentObject->getCode()) :
                    $this->__('New Sphinx Scope')
            );
        }

        return $this;
    }

    /**
     * Returns object title for error messages
     *
     * @return string
     */
    protected function _getObjectTitle()
    {
        return $this->__('Sphinx Scope');
    }

    /**
     * Edit sphinx attribute
     *
     */
    public function newAction()
    {
        $object = $this->_initObject();
        if (!$object) {
            return;
        }
        
        if ($object->getId()) {
            $this->_redirect('*/*/new');
            return;
        }

        $this->_initTitles($object);
        $this->loadLayout();
        $this->_setActiveMenu($this->_menu);
        $this->renderLayout();
    }
    
    public function createAction()
    {
        $data = $this->getRequest()->getPost($this->_prefix, array());
        
        if (!is_array($data) || !isset($data['name'])) {
            $this->_notifyErrors(array($this->__('Please fill in the form')), array(), '*/*/new');
            return;
        }
        
        $parentObject = null;
        
        if (!empty($data['parent_id'])) {
            $parentObject = $this->_getModel()->load($data['parent_id']);
        }
                
        $object = $this->_getModel();
        if ($parentObject) {
            $object->setParentId((int)$parentObject->getId());
        }
        $object->setName($data['name'])
            ->setValidationMode(EcomDev_Sphinx_Model_Attribute::VALIDATE_LIGHT);
        $errors = $object->validate();
        
        if ($errors === true) {
            try {
                $object->save();    
            } catch (Exception $e) {
                $errors = array($e->getMessage());
            }
        }
        
        if (is_array($errors)) {
            $this->_notifyErrors($errors, array(), '*/*/new');
        } else {
            $this->_getSession()->addSuccess(
                $this->__('Scope "%s" record was created, now you can edit its configuration', 
                    $object->getName()
                )
            );
            $this->_redirect('*/*/edit', array('scope_id' => $object->getId()));
        }
    }

}
