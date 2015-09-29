<?php

// Upgrade script from previous terrible version
/* @var $this Mage_Core_Model_Resource_Setup */
$this->startSetup();
$connection = $this->getConnection();

$attributeTable = $this->getTable('ecomdev_sphinx/attribute');
$scopeTable = $this->getTable('ecomdev_sphinx/scope');

$prefix = 'ecomdev_sphinx_%';
$prefixPosition = strpos($attributeTable, 'ecomdev_sphinx');

if ($prefixPosition > 0) {
    $prefix = substr($attributeTable, 0, $prefixPosition) . $prefix;
}

$backupTables = [$attributeTable, $scopeTable];
$backupRows = [];

foreach ($backupTables as $backupTable) {
    $backupRows[$backupTable] = [];
    if ($this->tableExists($backupTable)) {
        $select = $connection->select();
        $select->from($backupTable);
        $backupRows[$backupTable] = $connection->fetchAll($select);
    }
}

$allTables = $connection->fetchCol(
    $connection->quoteInto('SHOW TABLES LIKE ?', $prefix)
);

$allTriggers = $connection->fetchCol(
    $connection->quoteInto(
        sprintf('SHOW TRIGGERS WHERE %s LIKE ?', $connection->quoteIdentifier('Trigger')),
        '%ecomdev_sphinx_trigger%'
    )
);

usort($allTables, function ($v1, $v2) {
    if (strlen($v1) === strlen($v2)) {
        return 0;
    }

    if (strlen($v1) > strlen($v2)) {
        return 1;
    }

    return -1;
});

foreach (array_reverse($allTables) as $table) {
    $connection->dropTable($table);
}

foreach ($allTriggers as $trigger) {
    $connection->dropTrigger($trigger);
}

$this->endSetup();

// Include full installation script
include __DIR__ . DS . 'install-2.0.0.php';

$this->startSetup();

foreach ($backupRows as $table => $rows) {
    if (!isset($rows[0])) {
        continue;
    }

    $availableColumns = $connection->fetchCol(sprintf(
        'DESCRIBE %s',
        $connection->quoteIdentifier($table)
    ));

    $availableColumns = array_intersect($availableColumns, array_keys($rows[0]));

    if (empty($availableColumns)) {
        continue;
    }

    $availableColumns = array_combine($availableColumns, $availableColumns);
    $dataToInsert = [];
    foreach ($rows as $row) {
        $dataToInsert[] = array_intersect_key($row, $availableColumns);
    }

    $this->getConnection()->insertOnDuplicate(
        $table, $dataToInsert, $availableColumns
    );
}

if ($this->tableExists($this->getTable('index/process'))) {
    $this->getConnection()->update(
        $this->getTable('index/process'),
        array(
            'status' => 'require_reindex'
        ),
        array(
            'indexer_code LIKE ?' => 'sphinx\_%'
        )
    );
}

$this->endSetup();
