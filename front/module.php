<?php

include ("../../../inc/includes.php");

Session::checkRight("plugin_taskmaster_module", READ);

Html::header(PluginTaskmasterModule::getTypeName(Session::getPluralNumber()), PluginTaskmasterModule::getSearchURL(), "tools", "PluginTaskmasterModule");

PluginTaskmasterModule::showList();

Html::footer();
