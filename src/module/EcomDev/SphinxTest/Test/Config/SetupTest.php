<?php

/**
 * @module EcomDev_Sphinx
 */
class EcomDev_SphinxTest_Test_Config_SetupTest
    extends EcomDev_PHPUnit_Test_Case_Config
{
    public function testItHasScopeAndConfigTableAliasesDefined()
    {
        $this->assertTableAlias('ecomdev_sphinx/scope', 'ecomdev_sphinx_scope');
        $this->assertTableAlias('ecomdev_sphinx/attribute', 'ecomdev_sphinx_attribute');
    }
    
    public function testItHasIndexTableAliasesDefined()
    {
        $this->assertTableAlias('ecomdev_sphinx/index_category', 'ecomdev_sphinx_index_category');
        $this->assertTableAlias('ecomdev_sphinx/index_product', 'ecomdev_sphinx_index_product');
        $this->assertTableAlias('ecomdev_sphinx/index_product_option', 'ecomdev_sphinx_index_product_option');
        $this->assertTableAlias('ecomdev_sphinx/index_product_string', 'ecomdev_sphinx_index_product_string');
        $this->assertTableAlias('ecomdev_sphinx/index_product_text', 'ecomdev_sphinx_index_product_text');
        $this->assertTableAlias('ecomdev_sphinx/index_product_decimal', 'ecomdev_sphinx_index_product_decimal');
        $this->assertTableAlias('ecomdev_sphinx/index_product_timestamp', 'ecomdev_sphinx_index_product_timestamp');
        $this->assertTableAlias('ecomdev_sphinx/index_product_price', 'ecomdev_sphinx_index_product_price');
        $this->assertTableAlias('ecomdev_sphinx/index_metadata', 'ecomdev_sphinx_index_metadata');
    }
    
    public function testItHasSetupResource()
    {
        $this->assertSetupResourceDefined();
        $this->assertSchemeSetupExists();
    }
    
    public function testItHasSchemeSetupScriptAtFirstVersion()
    {
        $this->assertSchemeSetupScriptVersions('1.0.0', '1.0.0');
    }
    
    public function testItHasFirtsModuleVersionCorrectlySetForSetupScriptRun()
    {
        $this->assertModuleVersionGreaterThanOrEquals('1.0.0');
    }
    
    public function testItHasDataSetupScriptAtSecondVersion()
    {
        $this->assertDataSetupScriptVersions('1.0.0', '1.0.0');
    }

}
