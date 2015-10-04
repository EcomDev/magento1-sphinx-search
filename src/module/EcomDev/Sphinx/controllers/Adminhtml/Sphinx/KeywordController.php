<?php
class EcomDev_Sphinx_Adminhtml_Sphinx_KeywordController
    extends EcomDev_Sphinx_Controller_Adminhtml
{
    protected $_prefix = 'sphinx_keyword';
    protected $_idField = 'attribute_id';
    protected $_menu = 'catalog/sphinx/keyword';

    /**
     * Returns an instance of assortment model
     *
     * @return EcomDev_Sphinx_Model_Attribute
     */
    protected function _getModel()
    {
        return Mage::getModel('ecomdev_sphinx/keyword');
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
            ->_title($this->__('View Keywords'));

        return $this;
    }

    /**
     * Returns object title for error messages
     *
     * @return string
     */
    protected function _getObjectTitle()
    {
        return $this->__('Sphinx Keyword');
    }

    /**
     * Controller pre-dispatch method
     *
     * @return Mage_Adminhtml_Controller_Action
     */
    public function preDispatch()
    {
        if (!in_array($this->getRequest()->getActionName(), ['index', 'grid'])) {
            $this->getRequest()->setActionName('index');
        }

        parent::preDispatch();
        return $this;
    }


}
