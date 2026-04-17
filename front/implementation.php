<?php

include ("../../../inc/includes.php");

Session::checkRight("plugin_taskmaster_impl", READ);

Html::header(PluginTaskmasterImplementation::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], "tools", "PluginTaskmasterImplementation");

$_SESSION['glpilist_limit'] = 30;

if (PluginTaskmasterImplementation::canCreate()) {
    echo "<div class='center' style='margin-bottom: 20px;'>";
    echo "<a href='" . PluginTaskmasterImplementation::getFormURL() . "' class='btn btn-primary mb-2' style='color:white; padding: 10px 20px; font-weight: bold;'>";
    echo "<i class='fas fa-plus-circle' style='margin-right:8px;'></i>Adicionar Nova Implantação";
    echo "</a>";
    echo "</div>";
}

PluginTaskmasterImplementation::showList();

Html::footer();
