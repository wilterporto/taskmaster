<?php

include ("../../../inc/includes.php");

Session::checkRight("plugin_taskmaster_implementation", READ);

$impltask = new PluginTaskmasterImplementationTask();

if (isset($_POST["update"])) {
    $impltask->check($_POST["id"], UPDATE);
    $impltask->update($_POST);
    Html::redirect($CFG_GLPI['root_doc']."/plugins/taskmaster/front/implementation.form.php?id=".$_POST['plugin_taskmaster_implementations_id']);
}

Html::header(PluginTaskmasterImplementationTask::getTypeName(1), $_SERVER['PHP_SELF'], "tools", "PluginTaskmasterImplementation");

$impltask->display(['id' => $_GET["id"] ?? 0]);

Html::footer();
