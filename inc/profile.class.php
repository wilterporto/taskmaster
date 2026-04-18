<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginTaskmasterProfile extends CommonGLPI {
    static function getTypeName($nb = 0) {
        return _n('Perfil', 'Perfis', $nb, 'taskmaster');
    }

    function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        if ($item->getType() == 'Profile') {
            return __('Taskmaster', 'taskmaster');
        }
        return '';
    }

    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
        if ($item->getType() == 'Profile') {
            self::showForProfile($item);
        }
        return true;
    }

    static function showForProfile($prof) {
        $ID = $prof->getField('id');
        $rights = ProfileRight::getProfileRights($ID, [
            'plugin_taskmaster_module',
            'plugin_taskmaster_implementation'
        ]);

        echo "<form method='post' action='" . self::getFormURL() . "'>";
        echo "<input type='hidden' name='_glpi_csrf_token' value='" . Session::getNewCSRFToken() . "'>";
        echo "<input type='hidden' name='profiles_id' value='$ID'>";
        echo "<div class='spaced'>";
        echo "<table class='tab_cadre_fixehov'>";
        echo "<tr class='tab_bg_1'><th colspan='6'>" . __('TASKMASTER', 'taskmaster') . "</th></tr>";

        // Cabeçalho
        echo "<tr class='tab_bg_2'>";
        echo "<th>&nbsp;</th>";
        echo "<th class='center'>" . __('LER', 'taskmaster') . "</th>";
        echo "<th class='center'>" . __('ATUALIZAR', 'taskmaster') . "</th>";
        echo "<th class='center'>" . __('CRIAR', 'taskmaster') . "</th>";
        echo "<th class='center'>" . __('APAGAR', 'taskmaster') . "</th>";
        echo "<th class='center'>" . __('MARCAR/DESMARCAR TODOS', 'taskmaster') . "</th>";
        echo "</tr>";

        $elements = [
            'plugin_taskmaster_module'         => __('Cadastro do Módulo (Módulos, Tarefas e Subtarefas)', 'taskmaster'),
            'plugin_taskmaster_implementation' => __('Registro de Implantação', 'taskmaster')
        ];

        foreach ($elements as $right_name => $label) {
            $val = $rights[$right_name] ?? 0;
            echo "<tr class='tab_bg_1'>";
            echo "<td>$label</td>";

            // LER (1)
            echo "<td class='center'><input type='checkbox' name='_rights[$right_name][".READ."]' value='1' ".(($val & READ) ? "checked" : "")." class='chk_$right_name chk_col_read'></td>";
            // ATUALIZAR (4)
            echo "<td class='center'><input type='checkbox' name='_rights[$right_name][".UPDATE."]' value='1' ".(($val & UPDATE) ? "checked" : "")." class='chk_$right_name chk_col_update'></td>";
            // CRIAR (2)
            echo "<td class='center'><input type='checkbox' name='_rights[$right_name][".CREATE."]' value='1' ".(($val & CREATE) ? "checked" : "")." class='chk_$right_name chk_col_create'></td>";
            // APAGAR (8)
            echo "<td class='center'><input type='checkbox' name='_rights[$right_name][".PURGE."]' value='1' ".(($val & PURGE) ? "checked" : "")." class='chk_$right_name chk_col_purge'></td>";

            // Marcar todos da LINHA
            echo "<td class='center'><input type='checkbox' onclick=\"
                var checks = document.querySelectorAll('.chk_$right_name');
                for(var i=0; i<checks.length; i++) checks[i].checked = this.checked;
            \"></td>";
            echo "</tr>";
        }

        // Linha FINAL: Marcar todos da COLUNA
        echo "<tr class='tab_bg_2'>";
        echo "<td>" . __('MARCAR/DESMARCAR TODOS', 'taskmaster') . "</td>";
        echo "<td class='center'><input type='checkbox' onclick=\"var cs = document.querySelectorAll('.chk_col_read'); for(var i=0; i<cs.length; i++) cs[i].checked = this.checked;\"></td>";
        echo "<td class='center'><input type='checkbox' onclick=\"var cs = document.querySelectorAll('.chk_col_update'); for(var i=0; i<cs.length; i++) cs[i].checked = this.checked;\"></td>";
        echo "<td class='center'><input type='checkbox' onclick=\"var cs = document.querySelectorAll('.chk_col_create'); for(var i=0; i<cs.length; i++) cs[i].checked = this.checked;\"></td>";
        echo "<td class='center'><input type='checkbox' onclick=\"var cs = document.querySelectorAll('.chk_col_purge'); for(var i=0; i<cs.length; i++) cs[i].checked = this.checked;\"></td>";
        echo "<td>&nbsp;</td>";
        echo "</tr>";

        echo "</table>";

        echo "<div class='center mt-2'>";
        echo "<input type='submit' name='update' value='" . _sx('button', 'Save') . "' class='btn btn-primary'>";
        echo "</div>";
        echo "</div>";
        echo "</form>";
    }

    function updateProfileRights(array $input) {
        global $DB;
        if (isset($input['profiles_id'])) {
            $profiles_id = (int)$input['profiles_id'];
            
            // Lista fixa de permissões para garantir que desmarcar tudo funcione
            $permissions = [
                'plugin_taskmaster_module',
                'plugin_taskmaster_implementation'
            ];

            foreach ($permissions as $right_name) {
                $total = 0;
                if (isset($input['_rights'][$right_name])) {
                    foreach ($input['_rights'][$right_name] as $bit => $val) {
                        if ($val) {
                            $total |= (int)$bit;
                        }
                    }
                }

                // Verifica se já existe o registro para este direito e perfil
                $existing = $DB->request('glpi_profilerights', [
                    'profiles_id' => $profiles_id,
                    'name'        => $right_name
                ]);

                if (count($existing) > 0) {
                    $current = $existing->current();
                    $DB->update('glpi_profilerights', [
                        'rights' => $total
                    ], [
                        'id' => $current['id']
                    ]);
                } else {
                    $DB->insert('glpi_profilerights', [
                        'profiles_id' => $profiles_id,
                        'name'        => $right_name,
                        'rights'      => $total
                    ]);
                }
            }
        }
    }
}
