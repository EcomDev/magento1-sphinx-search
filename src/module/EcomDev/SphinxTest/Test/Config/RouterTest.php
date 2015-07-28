<?php

class EcomDev_SphinxTest_Test_Config_RouterTest
    extends EcomDev_PHPUnit_Test_Case_Config
{
    public function testItHasRouteDefinedToAdminhtml()
    {
        $this->assertRouteModule('adminhtml','EcomDev_Sphinx_Adminhtml', 'admin');
    }
}
