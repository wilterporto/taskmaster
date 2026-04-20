<?php

include ("../../../inc/includes.php");

Session::checkRight("plugin_taskmaster_module", READ);

$task = new PluginTaskmasterTask();

if (isset($_POST["add"])) {
    $task->check(-1, CREATE, $_POST);
    $task->add($_POST);
    Html::back();
} else if (isset($_POST["update"])) {
    $task->check($_POST["id"], UPDATE);
    $task->update($_POST);
    Html::back();
} else if (isset($_POST["purge"])) {
    $task->check($_POST["id"], PURGE);
    $task->delete($_POST, 1);
    Html::back();
}

Html::header(PluginTaskmasterTask::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], "tools", "PluginTaskmasterModule");

$task->display([
    'id' => $_GET["id"] ?? 0,
    'plugin_taskmaster_modules_id' => $_GET['plugin_taskmaster_modules_id'] ?? 0
]);

Html::footer();
