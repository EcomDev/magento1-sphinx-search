<?php

class EcomDev_Sphinx_Adminhtml_Sphinx_Word_FormController
    extends EcomDev_Sphinx_Controller_Adminhtml
{
    protected $_prefix = 'sphinx_word_form';
    protected $_idField = 'word_form_id';
    protected $_menu = 'catalog/sphinx/word_form';

    /**
     * Returns an instance of assortment model
     *
     * @return EcomDev_Sphinx_Model_Word_Form
     */
    protected function _getModel()
    {
        return Mage::getModel('ecomdev_sphinx/word_form');
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
            ->_title($this->__('Manage Word Forms'));

        if ($currentObject !== null) {
            $this->_title(
                $currentObject->getId() ?
                    $this->__('Edit Word Form "%s"', $currentObject->getName()) :
                    $this->__('New Word Form')
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
}
