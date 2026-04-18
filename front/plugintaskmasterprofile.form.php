<?php

include ("../../../inc/includes.php");

$prof = new PluginTaskmasterProfile();

if (isset($_POST["update"])) {
    Session::checkRight("profile", UPDATE);
    $prof->updateProfileRights($_POST);
    
    Session::addMessageAfterRedirect(__('Permissões salvas com sucesso', 'taskmaster'), true, INFO);
    Html::back();
}

Html::back();
