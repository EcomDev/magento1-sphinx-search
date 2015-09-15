<?php

$catalogInstaller = new Mage_Catalog_Model_Resource_Setup('core_setup');
$catalogInstaller->addAttribute(
    Mage_Catalog_Model_Category::ENTITY,
    'sphinx_scope',
    array(
        'type'                       => 'int',
        'label'                      => 'Sphinx Scope',
        'input'                      => 'select',
        'source'                     => 'ecomdev_sphinx/source_catalog_scope',
        'sort_order'                 => 10,
        'global'                     => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
        'group'                      => 'General Information',
        'required'                   => false
    )
);
