<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginTaskmasterConfig extends CommonDBTM {
    static protected $notable = true;

    static function getTypeName($nb = 0) {
        return __('Configuração Taskmaster', 'taskmaster');
    }

    static function getIcon() {
        return "fas fa-cogs";
    }

    static function canCreate() {
        return Session::haveRight('plugin_taskmaster_manage', CREATE);
    }

    static function canView() {
        return Session::haveRight('plugin_taskmaster_manage', READ);
    }

    static function getConfig($name) {
        global $DB;
        $req = $DB->request('glpi_plugin_taskmaster_configs', ['name' => $name]);
        if ($row = $req->next()) {
            return $row['value'];
        }
        return false;
    }

    static function setConfig($name, $value) {
        global $DB;
        $req = $DB->request('glpi_plugin_taskmaster_configs', ['name' => $name]);
        if ($row = $req->next()) {
            $DB->update('glpi_plugin_taskmaster_configs', ['value' => $value], ['id' => $row['id']]);
        } else {
            $DB->insert('glpi_plugin_taskmaster_configs', ['name' => $name, 'value' => $value]);
        }
    }

    /**
     * Retorna IDs de perfis que são admin ou super-admin (pelo is_default e interface)
     */
    static function getAdminProfileIds() {
        global $DB;
        $ids = [];
        // Perfis com interface 'central' e que são padrão (admin/super-admin do GLPI)
        $req = $DB->request([
            'FROM'  => 'glpi_profiles',
            'WHERE' => [
                'interface' => 'central',
                'id'        => [1, 3, 4] // IDs padrão: super-admin=4, admin=3, no-rights=1 (excluir 1)
            ]
        ]);
        // Mais robusto: busca pelos nomes
        $byName = $DB->request([
            'FROM'  => 'glpi_profiles',
            'WHERE' => ['name' => ['admin', 'super-admin', 'Super-Admin', 'Admin']]
        ]);
        foreach ($byName as $p) {
            $ids[] = $p['id'];
        }
        // Fallback: IDs 3 e 4 que são padrão do GLPI
        if (empty($ids)) {
            $ids = [3, 4];
        }
        return array_unique($ids);
    }

    /**
     * Retorna todos os perfis com interface 'central' (exceto os admin)
     */
    static function getAllCentralProfiles() {
        global $DB;
        $profiles = [];
        $req = $DB->request([
            'FROM'  => 'glpi_profiles',
            'WHERE' => ['interface' => 'central'],
            'ORDER' => 'name ASC'
        ]);
        foreach ($req as $row) {
            $profiles[] = $row;
        }
        return $profiles;
    }

    /**
     * Retorna os IDs de perfis que têm um determinado direito
     */
    static function getProfilesWithRight($rightName, $minRight = READ) {
        global $DB;
        $ids = [];
        $req = $DB->request('glpi_profilerights', [
            'name'   => $rightName,
            'rights' => ['>=', $minRight]
        ]);
        foreach ($req as $row) {
            $ids[] = $row['profiles_id'];
        }
        return $ids;
    }

    /**
     * Salva os direitos de implantação para um perfil
     */
    static function setProfileImplRight($profileId, $rights) {
        global $DB;
        $existing = $DB->request('glpi_profilerights', [
            'profiles_id' => $profileId,
            'name'        => 'plugin_taskmaster_impl'
        ]);
        if (count($existing) == 0) {
            if ($rights > 0) {
                $DB->insert('glpi_profilerights', [
                    'profiles_id' => $profileId,
                    'name'        => 'plugin_taskmaster_impl',
                    'rights'      => $rights
                ]);
            }
        } else {
            $DB->update('glpi_profilerights', [
                'rights' => $rights
            ], [
                'profiles_id' => $profileId,
                'name'        => 'plugin_taskmaster_impl'
            ]);
        }
    }

    function showForm($id, array $options = []) {
        global $DB;

        $allProfiles   = self::getAllCentralProfiles();
        $adminIds      = self::getAdminProfileIds();

        // Perfis que podem CRIAR implantações
        $canCreate = self::getProfilesWithRight('plugin_taskmaster_impl', CREATE);
        // Perfis que podem VER implantações (mas não criar)
        $canRead   = self::getProfilesWithRight('plugin_taskmaster_impl', READ);

        echo "<div class='center'>";
        echo "<form method='post' action='" . $_SERVER['PHP_SELF'] . "'>";
        echo "<input type='hidden' name='_glpi_csrf_token' value='" . Session::getNewCSRFToken() . "'>";

        echo "<table class='tab_cadre_fixe' style='max-width: 800px;'>";
        echo "<tr class='tab_bg_1'><th colspan='4'>" . __('Permissões de Acesso ao Taskmaster', 'taskmaster') . "</th></tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<th style='width: 40%;'>Perfil</th>";
        echo "<th class='center' style='width: 20%;'>Sem Acesso</th>";
        echo "<th class='center' style='width: 20%;'>Somente Leitura <span style='color:red;'>*</span></th>";
        echo "<th class='center' style='width: 20%;'>Criação/Edição <span style='color:red;'>*</span></th>";
        echo "</tr>";

        foreach ($allProfiles as $profile) {
            $pid      = $profile['id'];
            $isAdmin  = in_array($pid, $adminIds);
            $hasCreate = in_array($pid, $canCreate);
            $hasRead   = in_array($pid, $canRead) && !$hasCreate;

            if ($isAdmin) {
                // Admin sempre tem acesso total — somente exibe, não permite alterar
                echo "<tr class='tab_bg_1'>";
                echo "<td><strong>" . $profile['name'] . "</strong> <span style='color:#888; font-size:11px;'>(administrador)</span></td>";
                echo "<td class='center'>—</td>";
                echo "<td class='center'>—</td>";
                echo "<td class='center'><input type='checkbox' disabled checked title='Administradores sempre têm acesso total'></td>";
                echo "</tr>";
            } else {
                $checkedNone   = (!$hasRead && !$hasCreate) ? 'checked' : '';
                $checkedRead   = $hasRead   ? 'checked' : '';
                $checkedCreate = $hasCreate ? 'checked' : '';

                echo "<tr class='tab_bg_1'>";
                echo "<td>" . htmlspecialchars($profile['name']) . "</td>";
                echo "<td class='center'><input type='radio' name='impl_right[$pid]' value='0' $checkedNone></td>";
                echo "<td class='center'><input type='radio' name='impl_right[$pid]' value='" . READ . "' $checkedRead></td>";
                echo "<td class='center'><input type='radio' name='impl_right[$pid]' value='" . (CREATE | READ | UPDATE) . "' $checkedCreate></td>";
                echo "</tr>";
            }
        }

        if (empty($allProfiles)) {
            echo "<tr><td colspan='4' class='center' style='color:#888;'>Nenhum perfil encontrado.</td></tr>";
        }

        echo "<tr class='tab_bg_2'>";
        echo "<td colspan='4' class='center' style='padding: 10px;'>";
        echo "<small style='color:#666;'><i class='fas fa-info-circle'></i> Módulos de implantação e configurações são sempre restritos a administradores.</small>";
        echo "</td></tr>";

        echo "<tr class='tab_bg_2'><td colspan='4' class='center'>";
        echo "<input type='submit' name='update_config' value='" . _sx('button', 'Save') . "' class='submit'>";
        echo "</td></tr>";

        echo "</table>";
        echo "</form>";
        echo "</div>";
    }
}
