<?php

include ("../../../inc/includes.php");

Session::checkRight("plugin_taskmaster_module", READ);

Html::header(PluginTaskmasterSubtask::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], "tools", "PluginTaskmasterModule");

$_SESSION['glpilist_limit'] = 30;

if (PluginTaskmasterSubtask::canCreate()) {
    echo "<div class='center' style='margin-bottom: 20px;'>";
    echo "<a href='" . PluginTaskmasterSubtask::getFormURL() . "' class='btn btn-primary mb-2' style='color:white; padding: 10px 20px; font-weight: bold;'>";
    echo "<i class='fas fa-plus-circle' style='margin-right:8px;'></i>Adicionar Nova Subtarefa";
    echo "</a>";
    echo "</div>";
}

Search::show('PluginTaskmasterSubtask', ['display_export' => false]);

Html::footer();
