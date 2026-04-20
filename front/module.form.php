<?php

include ("../../../inc/includes.php");

Session::checkRight("plugin_taskmaster_module", READ);

$module = new PluginTaskmasterModule();

if (isset($_POST["add"])) {
    $module->check(-1, CREATE, $_POST);
    $module->add($_POST);
    Html::back();
} else if (isset($_POST["update"])) {
    $module->check($_POST["id"], UPDATE);
    $module->update($_POST);
    Html::back();
} else if (isset($_POST["purge"])) {
    $module->check($_POST["id"], PURGE);
    $module->delete($_POST, 1);
    $module->redirectToList();
} else if (isset($_POST["delete"])) {
    $module->check($_POST["id"], PURGE);
    $module->delete($_POST, 1);
    $module->redirectToList();
} else if (isset($_POST["restore"])) {
    $module->check($_POST["id"], PURGE);
    $module->restore($_POST);
    $module->redirectToList();
}

Html::header(PluginTaskmasterModule::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], "tools", "PluginTaskmasterModule");

$module->display(['id' => $_GET["id"] ?? 0]);

Html::footer();
