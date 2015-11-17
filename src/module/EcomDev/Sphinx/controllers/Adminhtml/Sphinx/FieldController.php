<?php

class EcomDev_Sphinx_Adminhtml_Sphinx_FieldController
    extends EcomDev_Sphinx_Controller_Adminhtml
{
    protected $_prefix = 'sphinx_field';
    protected $_idField = 'field_id';
    protected $_menu = 'catalog/sphinx/field';

    /**
     * Returns an instance of field model
     *
     * @return EcomDev_Sphinx_Model_Field
     */
    protected function _getModel()
    {
        return Mage::getModel('ecomdev_sphinx/field');
    }
    
    /**
     * Initializes admin titles
     *
     * @param null|EcomDev_Sphinx_Model_Field $currentObject
     * @return string
     */
    protected function _initTitles(EcomDev_Sphinx_Model_AbstractModel $currentObject = null)
    {
        $this->_title($this->__('Catalog'))
            ->_title($this->__('Sphinx Search'))
            ->_title($this->__('Manage Virtual Fields'));

        if ($currentObject !== null) {
            $this->_title(
                $currentObject->getId() ?
                    $this->__('Edit Virtual Field "%s"', $currentObject->getName()) :
                    $this->__('New Virtual Field')
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
        return $this->__('Virtual Field');
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
        
        if (!is_array($data) || empty($data['name']) || empty($data['code']) || empty($data['type'])) {
            $this->_notifyErrors(array($this->__('Please fill in the form')), array(), '*/*/new');
            return;
        }

        $object = $this->_getModel();
        $object->setName($data['name'])
            ->setType($data['type'])
            ->setCode($data['code'])
            ->setValidationMode(EcomDev_Sphinx_Model_Attribute::VALIDATE_LIGHT);

        if (!empty($data['related_attribute'])) {
            $object->setConfiguration(['related_attribute' => $data['related_attribute']]);
        }

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
                $this->__('Field "%s" record was created, now you can edit its configuration',
                    $object->getName()
                )
            );
            $this->_redirect('*/*/edit', array('field_id' => $object->getId()));
        }
    }

}
