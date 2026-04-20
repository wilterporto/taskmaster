<?php

include ("../../../inc/includes.php");

Session::checkRight("plugin_taskmaster_implementation", READ);

$impl = new PluginTaskmasterImplementation();

if (isset($_POST["add"])) {
    $impl->check(-1, CREATE, $_POST);
    if ($impl->add($_POST)) {
        Html::redirect($impl->getFormURL()."?id=".$impl->fields['id']);
    } else {
        Html::back();
    }
} else if (isset($_POST["update"])) {
    $impl->check($_POST["id"], UPDATE);
    $impl->update($_POST);
    Html::back();
} else if (isset($_POST["add_module"])) {
    $impl->check($_POST["id"], UPDATE);
    $impl->addModule($_POST["id"], $_POST["add_module_id"]);
    Html::back();
} else if (isset($_POST["remove_modules"])) {
    $impl->check($_POST["id"], UPDATE);
    if (!empty($_POST["delete_modules"])) {
        foreach ($_POST["delete_modules"] as $mId) {
            $impl->removeModule($_POST["id"], $mId);
        }
    }
    Html::back();
} else if (isset($_POST["delete"])) {
    $impl->check($_POST["id"], PURGE);
    $impl->delete($_POST, 1);
    $impl->redirectToList();
} else if (isset($_POST["restore"])) {
    $impl->check($_POST["id"], PURGE);
    $impl->restore($_POST);
    $impl->redirectToList();
}

Html::header(PluginTaskmasterImplementation::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], "tools", "PluginTaskmasterImplementation");

$impl->display(['id' => $_GET["id"] ?? 0]);

Html::footer();
