<?php

include ("../../../inc/includes.php");

$prof = new PluginTaskmasterProfile();

if (isset($_POST["update"])) {
   Session::checkRight("profile", UPDATE);
   $prof->updateProfileRights($_POST);
   Html::back();
}

Html::back();
