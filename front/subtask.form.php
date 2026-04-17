<?php

include ("../../../inc/includes.php");

Session::checkRight("plugin_taskmaster_manage", READ);

$subtask = new PluginTaskmasterSubtask();

if (isset($_POST["add"])) {
    $subtask->check(-1, CREATE, $_POST);
    $subtask->add($_POST);
    Html::back();
} else if (isset($_POST["update"])) {
    $subtask->check($_POST["id"], UPDATE);
    $subtask->update($_POST);
    Html::back();
} else if (isset($_POST["purge"])) {
    $subtask->check($_POST["id"], PURGE);
    $subtask->delete($_POST, 1);
    Html::back();
}

Html::header(PluginTaskmasterSubtask::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], "tools", "PluginTaskmasterModule");

$subtask->display([
    'id' => $_GET["id"] ?? 0,
    'plugin_taskmaster_tasks_id' => $_GET['plugin_taskmaster_tasks_id'] ?? 0
]);

Html::footer();
