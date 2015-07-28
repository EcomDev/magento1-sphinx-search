<?php

class EcomDev_SphinxTest_Test_Config_ModelsTest
    extends EcomDev_PHPUnit_Test_Case_Config
{
    public function testItCorrectlyResolvesModelAliases()
    {
        $this->assertModelAlias('ecomdev_sphinx/config', 'EcomDev_Sphinx_Model_Config');
        $this->assertModelAlias('ecomdev_sphinx/attribute', 'EcomDev_Sphinx_Model_Attribute');
        $this->assertModelAlias('ecomdev_sphinx/scope', 'EcomDev_Sphinx_Model_Scope');
    }
    
    public function testItCorrectlyResolvesResourceModelAliases()
    {
        $this->assertResourceModelAlias('ecomdev_sphinx/attribute', 'EcomDev_Sphinx_Model_Resource_Attribute');
        $this->assertResourceModelAlias('ecomdev_sphinx/scope', 'EcomDev_Sphinx_Model_Resource_Scope');
    }
    
    public function testItHasHelperAliasDefined()
    {
        $this->assertHelperAlias('ecomdev_sphinx', 'EcomDev_Sphinx_Helper_Data');
    }
    
    public function testItHasBlockAliasDefined()
    {
        $this->assertBlockAlias(
            'ecomdev_sphinx/adminhtml_attribute',
            'EcomDev_Sphinx_Block_Adminhtml_Attribute'
        );
        
        $this->assertBlockAlias(
            'ecomdev_sphinx/adminhtml_attribute_grid',
            'EcomDev_Sphinx_Block_Adminhtml_Attribute_Grid'
        );
        
        $this->assertBlockAlias(
            'ecomdev_sphinx/adminhtml_attribute_edit',
            'EcomDev_Sphinx_Block_Adminhtml_Attribute_Edit'
        );
        
        $this->assertBlockAlias(
            'ecomdev_sphinx/adminhtml_attribute_edit_form',
            'EcomDev_Sphinx_Block_Adminhtml_Attribute_Edit_Form'
        );
    }
}
