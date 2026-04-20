<?php
include("../../../inc/includes.php");
$auth = new Auth();
$auth->login('glpi', 'glpi', true); // generic login
$_SESSION["glpiactiveprofile"] = ["id" => 4]; // usually super-admin
var_dump(Session::haveRight('plugin_taskmaster_module', CREATE));
$module = new PluginTaskmasterModule();
var_dump($module->canCreateItem());
var_dump($module->getFormURL());
var_dump($module->getSearchURL());
