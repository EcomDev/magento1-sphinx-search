<?php
class EcomDev_Sphinx_Adminhtml_Sphinx_AttributeController
    extends EcomDev_Sphinx_Controller_Adminhtml
{
    protected $_prefix = 'sphinx_attribute';
    protected $_idField = 'attribute_id';
    protected $_menu = 'catalog/sphinx/attribute';

    /**
     * Returns an instance of assortment model
     *
     * @return EcomDev_Sphinx_Model_Attribute
     */
    protected function _getModel()
    {
        return Mage::getModel('ecomdev_sphinx/attribute');
    }

    /**
     * Loads object for a controller, returns true if load operation worked
     *
     * @param EcomDev_Sphinx_Model_Attribute $object
     * @param $id
     * @return bool
     */
    protected function _loadObject(EcomDev_Sphinx_Model_AbstractModel $object, $id)
    {
        $object->load($id);
        
        if (!$object->getId()) {
            $object->setId($id);
        }

        return $object->getAttributeCode() !== false;
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
            ->_title($this->__('Manage Attributes'));

        if ($currentObject !== null) {
            $this->_title(
                $currentObject->getId() ?
                    $this->__('Edit Sphinx Attribute %s', $currentObject->getAttributeCode()) :
                    $this->__('New Sphinx Attribute')
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
        return $this->__('Sphinx Attribute');
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
        $attribute = Mage::getSingleton('eav/config')
            ->getAttribute(Mage_Catalog_Model_Product::ENTITY, $this->getRequest()->getPost('attribute_id'));
        
        if ($attribute) {
            $this->getRequest()
                ->setParam('attribute_id', $attribute->getId());
        }
        
        $object = $this->_initObject();
        
        if (!$object) {
            return;
        }
        
        $object->setValidationMode(EcomDev_Sphinx_Model_Attribute::VALIDATE_LIGHT);
        $errors = $object->validate();
        
        if ($errors === true) {
            try {
                $object->isObjectNew(true);
                $object
                    ->save();    
            } catch (Exception $e) {
                $errors = array($e->getMessage());
            }
        }
        
        if (is_array($errors)) {
            $this->_notifyErrors($errors, array(), '*/*/new');
        } else {
            $this->_getSession()->addSuccess(
                $this->__('Attribute "%s" record was created, now you can edit its properties', 
                    $object->getAttributeCode()
                )
            );
            $this->_redirect('*/*/edit', array('attribute_id' => $object->getId()));
        }
    }

}
