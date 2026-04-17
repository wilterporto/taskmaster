<?php

include ("../../../inc/includes.php");

Session::checkRight("plugin_taskmaster_manage", READ);

$plugin_config = new PluginTaskmasterConfig();

if (isset($_POST["update_config"])) {
    Session::checkRight("plugin_taskmaster_manage", UPDATE);

    $implRights = $_POST['impl_right'] ?? [];

    foreach ($implRights as $profileId => $rights) {
        $profileId = (int) $profileId;
        $rights    = (int) $rights;

        PluginTaskmasterConfig::setProfileImplRight($profileId, $rights);
    }

    // Garante que admins sempre tenham acesso total
    global $DB;
    $allRights = CREATE | READ | UPDATE | PURGE;
    $adminProfiles = $DB->request([
        'FROM'  => 'glpi_profiles',
        'WHERE' => ['name' => ['admin', 'super-admin', 'Super-Admin', 'Admin']]
    ]);
    foreach ($adminProfiles as $adm) {
        PluginTaskmasterConfig::setProfileImplRight($adm['id'], $allRights);
        // Garante também o direito de gerenciar módulos
        $existing = $DB->request('glpi_profilerights', [
            'profiles_id' => $adm['id'],
            'name'        => 'plugin_taskmaster_manage'
        ]);
        if (count($existing) == 0) {
            $DB->insert('glpi_profilerights', [
                'profiles_id' => $adm['id'],
                'name'        => 'plugin_taskmaster_manage',
                'rights'      => $allRights
            ]);
        }
    }

    Session::addMessageAfterRedirect("Configurações salvas com sucesso.", false, INFO);
    Html::back();
}

Html::header('Configurações', $_SERVER['PHP_SELF'], "tools", "PluginTaskmasterConfig");

$plugin_config->showForm(1);

Html::footer();
