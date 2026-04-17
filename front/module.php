<?php

include ("../../../inc/includes.php");

Session::checkRight("plugin_taskmaster_manage", READ);

Html::header(PluginTaskmasterModule::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], "tools", "PluginTaskmasterModule");

PluginTaskmasterModule::showList();

Html::footer();
