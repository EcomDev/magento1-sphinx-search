<?php

/**
 * @module EcomDev_Sphinx
 */
class EcomDev_SphinxTest_Test_Config_LayoutTest
    extends EcomDev_PHPUnit_Test_Case_Config
{
    public function testItHasLayoutFileDefinedForAdminPanel()
    {
        $this->assertLayoutFileDefined('adminhtml', 'ecomdev/sphinx.xml');
        $this->assertLayoutFileExists('adminhtml', 'ecomdev/sphinx.xml');
    }
}
