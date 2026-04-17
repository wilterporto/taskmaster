<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginTaskmasterProfile extends Profile {
    static function getTypeName($nb = 0) {
        return _n('Perfil', 'Perfis', $nb, 'taskmaster');
    }

    static function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        if ($item->getType() == 'Profile') {
            return __('Taskmaster', 'taskmaster');
        }
        return '';
    }

    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
        if ($item->getType() == 'Profile') {
            $ID = $item->getField('id');
            self::showForProfile($item);
        }
        return true;
    }

    static function showForProfile(Profile $prof) {
        global $DB;

        $ID = $prof->getField('id');
        $html = "
        <form method='post' action='" . Toolbox::getItemTypeFormURL(__CLASS__) . "'>
            <input type='hidden' name='profiles_id' value='$ID'>
            <table class='tab_cadre_fixe'>
                <tr>
                    <th colspan='2'>" . __('Permissões do Taskmaster', 'taskmaster') . "</th>
                </tr>
                <tr class='tab_bg_1'>
                    <td>" . __('Gerenciar Módulos e Configurações', 'taskmaster') . "</td>
                    <td>
                        <select name='plugin_taskmaster_manage'>
                            <option value='0' ".(!ProfileRight::checkProfileRight($ID, 'plugin_taskmaster_manage') ? "selected" : "").">" . __('No') . "</option>
                            <option value='".(CREATE | READ | UPDATE | PURGE)."' ".(ProfileRight::checkProfileRight($ID, 'plugin_taskmaster_manage') ? "selected" : "").">" . __('Yes') . "</option>
                        </select>
                    </td>
                </tr>
                <tr class='tab_bg_1'>
                    <td>" . __('Gerenciar Implantações', 'taskmaster') . "</td>
                    <td>
                        <select name='plugin_taskmaster_impl'>
                            <option value='0' ".(!ProfileRight::checkProfileRight($ID, 'plugin_taskmaster_impl') ? "selected" : "").">" . __('No') . "</option>
                            <option value='".(CREATE | READ | UPDATE | PURGE)."' ".(ProfileRight::checkProfileRight($ID, 'plugin_taskmaster_impl') ? "selected" : "").">" . __('Yes') . "</option>
                        </select>
                    </td>
                </tr>
                <tr class='tab_bg_2'>
                    <td colspan='2' class='center'>
                        <input type='submit' name='update' value='" . _sx('button', 'Save') . "' class='submit'>
                    </td>
                </tr>
            </table>
            " . Html::generatePNFormFields($_SESSION['glpi_use_mode'] === Session::NORMAL_MODE) . "
        </form>";
        echo $html;
    }

    function updateProfileRights(array $input) {
        if (isset($input['profiles_id'])) {
            $rights = [
                'plugin_taskmaster_manage' => isset($input['plugin_taskmaster_manage']) ? (int)$input['plugin_taskmaster_manage'] : 0,
                'plugin_taskmaster_impl' => isset($input['plugin_taskmaster_impl']) ? (int)$input['plugin_taskmaster_impl'] : 0,
            ];
            ProfileRight::addProfileRights([$input['profiles_id'] => $rights]);
        }
    }
}
