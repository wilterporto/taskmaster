<?php

include ("../../../inc/includes.php");

Session::checkRight("plugin_taskmaster_module", READ);

$subtask = new PluginTaskmasterSubtask();

if (isset($_POST["add"])) {
    $subtask->check(-1, CREATE, $_POST);
    if ($subtask->add($_POST)) {
        Session::addMessageAfterRedirect("Subtarefa adicionada com sucesso!", false, INFO);
        $task = new PluginTaskmasterTask();
        $task->getFromDB($_POST["plugin_taskmaster_tasks_id"]);
        $mid = $task->fields['plugin_taskmaster_modules_id'];
        $module = new PluginTaskmasterModule();
        Html::redirect($module->getFormURL()."?id=".$mid);
    } else {
        Html::back();
    }
} else if (isset($_POST["update"])) {
    $subtask->check($_POST["id"], UPDATE);
    if ($subtask->update($_POST)) {
        Session::addMessageAfterRedirect("Subtarefa atualizada com sucesso!", false, INFO);
        $tid = $_POST["plugin_taskmaster_tasks_id"] ?? $subtask->fields['plugin_taskmaster_tasks_id'];
        $task = new PluginTaskmasterTask();
        $task->getFromDB($tid);
        $mid = $task->fields['plugin_taskmaster_modules_id'];
        $module = new PluginTaskmasterModule();
        Html::redirect($module->getFormURL()."?id=".$mid);
    } else {
        Html::back();
    }
} else if (isset($_POST["purge"])) {
    $subtask->check($_POST["id"], PURGE);
    $tid = $subtask->fields['plugin_taskmaster_tasks_id'];
    $task = new PluginTaskmasterTask();
    $task->getFromDB($tid);
    $mid = $task->fields['plugin_taskmaster_modules_id'];
    if ($subtask->delete($_POST, 1)) {
        Session::addMessageAfterRedirect("Subtarefa excluída com sucesso!", false, INFO);
        $module = new PluginTaskmasterModule();
        Html::redirect($module->getFormURL()."?id=".$mid);
    } else {
        Html::back();
    }
}

Html::header(PluginTaskmasterSubtask::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], "tools", "PluginTaskmasterModule");

$subtask->display([
    'id' => $_GET["id"] ?? 0,
    'plugin_taskmaster_tasks_id' => $_GET['plugin_taskmaster_tasks_id'] ?? 0
]);

Html::footer();
