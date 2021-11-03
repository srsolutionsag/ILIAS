<#1>
<?php
/** @var $ilDB ilDBInterface */
if (!$ilDB->tableColumnExists('il_resource', 'rid')) {
    $ilDB->renameTableColumn(
        'il_resource',
        'identification',
        'rid'
    );
}
if (!$ilDB->tableColumnExists('il_resource_info', 'rid')) {
    $ilDB->renameTableColumn(
        'il_resource_info',
        'identification',
        'rid'
    );
}
if (!$ilDB->tableColumnExists('il_resource_revision', 'rid')) {
    $ilDB->renameTableColumn(
        'il_resource_revision',
        'identification',
        'rid'
    );
}
if (!$ilDB->tableColumnExists('il_resource_stakeh', 'rid')) {
    $ilDB->renameTableColumn(
        'il_resource_stakeh',
        'identification',
        'rid'
    );
}
?>
<#2>
<?php
/** @var $ilDB ilDBInterface */
$attributes = [
    'length' => 64,
    'notnull' => true,
    'default' => '',
];
$ilDB->modifyTableColumn(
    'il_resource',
    'rid',
    $attributes
);
$ilDB->modifyTableColumn(
    'il_resource_info',
    'rid',
    $attributes
);
$ilDB->modifyTableColumn(
    'il_resource_revision',
    'rid',
    $attributes
);
$ilDB->modifyTableColumn(
    'il_resource_stakeh',
    'rid',
    $attributes
);
$ilDB->modifyTableColumn(
    'file_data',
    'rid',
    $attributes
);
?>
<#3>
<?php
/** @var $ilDB ilDBInterface */
if (!$ilDB->tableColumnExists('il_resource_info', 'version_number')) {
    $ilDB->addTableColumn(
        'il_resource_info',
        'version_number',
        [
            'type' => 'integer',
            'length' => 8
        ]
    );

    $ilDB->manipulate("UPDATE il_resource_info
JOIN il_resource_revision ON il_resource_info.internal = il_resource_revision.internal
SET il_resource_info.version_number = il_resource_revision.version_number
");
}

?>
<#4>
<?php
/** @var $ilDB ilDBInterface */
if ($ilDB->tableColumnExists('il_resource_revision', 'internal')) {
    $ilDB->dropTableColumn('il_resource_revision', 'internal');
    $ilDB->addPrimaryKey(
        'il_resource_revision',
        [
            'rid',
            'version_number',
        ]
    );
}
if ($ilDB->tableColumnExists('il_resource_info', 'internal')) {
    $ilDB->dropTableColumn('il_resource_info', 'internal');
    $ilDB->addPrimaryKey(
        'il_resource_info',
        [
            'rid',
            'version_number',
        ]
    );
}
if ($ilDB->tableColumnExists('il_resource_stakeh', 'internal')) {
    $ilDB->dropTableColumn('il_resource_stakeh', 'internal');
    $ilDB->addPrimaryKey(
        'il_resource_stakeh',
        [
            'rid',
            'stakeholder_id',
        ]
    );
}
?>
<#5>
<?php
/** @var $ilDB ilDBInterface */
if (!$ilDB->tableExists('il_resource_stkh_u')) {
    $ilDB->renameTable('il_resource_stakeh', 'il_resource_stkh_u');
    $ilDB->createTable(
        'il_resource_stkh',
        [
            'id' => ['type' => 'text', 'length' => 32, 'notnull' => true, 'default' => ''],
            'class_name' => ['type' => 'text', 'length' => 250, 'notnull' => true, 'default' => ''],
        ]
    );
    $ilDB->addPrimaryKey('il_resource_stkh', ['id']);
    $ilDB->manipulate("INSERT INTO il_resource_stkh (id, class_name) SELECT DISTINCT stakeholder_id, stakeholder_class FROM il_resource_stkh_u;");
}

if ($ilDB->tableColumnExists('il_resource_stkh_u', 'stakeholder_class')) {
    $ilDB->dropTableColumn('il_resource_stkh_u', 'stakeholder_class');
}
?>
<#6>
<?php
/** @var $ilDB ilDBInterface */
$attributes = [
    'notnull' => true,
    'default' => '',
];
$table_fields = [
    'il_resource' => ['storage_id'],
    'il_resource_info' => ['title', 'size', 'creation_date'],
    'il_resource_revision' => ['owner_id', 'title'],
];
foreach ($table_fields as $table => $fields) {
    foreach ($fields as $field) {
        $ilDB->modifyTableColumn(
            $table,
            $field,
            $attributes
        );
    }
}
?>
<#7>
<?php
/** @var $ilDB ilDBInterface */
if (!$ilDB->indexExistsByFields('file_data', ['rid'])) {
    $ilDB->addIndex('file_data', ['rid'], 'i1');
}
?>
