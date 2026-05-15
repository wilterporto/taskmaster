<?php
include ("../../../inc/includes.php");

echo "Active Entities: " . implode(',', Session::getActiveEntities()) . "\n";

global $DB;
$req = $DB->request('glpi_plugin_taskmaster_implementations');
echo "Total Implementations in DB: " . count($req) . "\n";
foreach ($req as $row) {
    echo "ID: {$row['id']}, Name: {$row['name']}, Entity ID: {$row['entities_id']}\n";
}
