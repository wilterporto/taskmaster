<?php

include ("../../../inc/includes.php");

Session::checkRight("plugin_taskmaster_module", READ);

$task = new PluginTaskmasterTask();

if (isset($_POST["add"])) {
    $task->check(-1, CREATE, $_POST);
    if ($task->add($_POST)) {
        Session::addMessageAfterRedirect("Tarefa adicionada com sucesso!", false, INFO);
        $module = new PluginTaskmasterModule();
        Html::redirect($module->getFormURL()."?id=".$_POST["plugin_taskmaster_modules_id"]);
    } else {
        Html::back();
    }
} else if (isset($_POST["update"])) {
    $task->check($_POST["id"], UPDATE);
    if ($task->update($_POST)) {
        Session::addMessageAfterRedirect("Tarefa atualizada com sucesso!", false, INFO);
        $mid = $_POST["plugin_taskmaster_modules_id"] ?? $task->fields['plugin_taskmaster_modules_id'];
        $module = new PluginTaskmasterModule();
        Html::redirect($module->getFormURL()."?id=".$mid);
    } else {
        Html::back();
    }
} else if (isset($_POST["purge"])) {
    $task->check($_POST["id"], PURGE);
    $mid = $task->fields['plugin_taskmaster_modules_id'];
    if ($task->delete($_POST, 1)) {
        Session::addMessageAfterRedirect("Tarefa excluída com sucesso!", false, INFO);
        $module = new PluginTaskmasterModule();
        Html::redirect($module->getFormURL()."?id=".$mid);
    } else {
        Html::back();
    }
}

Html::header(PluginTaskmasterTask::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], "tools", "PluginTaskmasterModule");

$task->display([
    'id' => $_GET["id"] ?? 0,
    'plugin_taskmaster_modules_id' => $_GET['plugin_taskmaster_modules_id'] ?? 0
]);

Html::footer();
