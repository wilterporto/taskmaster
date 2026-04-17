<?php

include ("../../../inc/includes.php");

Session::checkRight("plugin_taskmaster_impl", READ);

$implsubtask = new PluginTaskmasterImplementationSubtask();

if (isset($_POST["update"])) {
    $implsubtask->check($_POST["id"], UPDATE);
    $implsubtask->update($_POST);
    
    // Redirect back to implementation
    global $DB;
    $impltask_id = $_POST['plugin_taskmaster_implementationtasks_id'];
    $req = $DB->request('glpi_plugin_taskmaster_implementationtasks', ['id' => $impltask_id]);
    if ($row = $req->next()) {
        Html::redirect($CFG_GLPI['root_doc']."/plugins/taskmaster/front/implementation.form.php?id=".$row['plugin_taskmaster_implementations_id']);
    } else {
        Html::back();
    }
}

Html::header(PluginTaskmasterImplementationSubtask::getTypeName(1), $_SERVER['PHP_SELF'], "tools", "PluginTaskmasterImplementation");

$implsubtask->display(['id' => $_GET["id"] ?? 0]);

Html::footer();
