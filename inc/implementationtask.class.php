<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginTaskmasterImplementationTask extends CommonDBTM {
    static $rightname = 'plugin_taskmaster_impl';
    public $dohistory = true;

    static function getTypeName($nb = 0) {
        return _n('Tarefa da Implantação', 'Tarefas da Implantação', $nb, 'taskmaster');
    }

    public static function getSearchURL($full = true) {
        return PluginTaskmasterImplementation::getSearchURL($full);
    }

    function showForm($id, array $options = []) {
        $this->initForm($id, $options);
        $this->showFormHeader($options);

        $task = new PluginTaskmasterTask();
        $task->getFromDB($this->fields['plugin_taskmaster_tasks_id']);
        
        echo "<tr class='tab_bg_1'>";
        echo "<th colspan='2'>Editar Tarefa: " . $task->fields['name'] . "</th>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td><label for='status'>Status <span style='color:red;'>*</span></label></td>";
        echo "<td>";
        $statuses = [
            0 => 'Não iniciado',
            1 => 'Planejado',
            2 => 'Em andamento',
            3 => 'Concluído',
            4 => 'Não optante'
        ];
        Dropdown::showFromArray('status', $statuses, [
            'value' => $this->fields['status'],
            'on_change' => 'checkStatusOptante(this.value)'
        ]);
        echo "</td>";
        echo "</tr>";

        // Filtro de perfis para analistas
        $analyst_profiles = json_decode(PluginTaskmasterConfig::getConfig('analyst_profiles') ?: '[]', true);
        $user_options = ['name' => 'users_id_analyst', 'value' => $this->fields['users_id_analyst'], 'display_emptychoice' => true];
        
        if (!empty($analyst_profiles)) {
            $profile_ids = implode(',', array_map('intval', $analyst_profiles));
            $user_options['condition'] = ["`glpi_users`.`id` IN (SELECT `users_id` FROM `glpi_profiles_users` WHERE `profiles_id` IN ($profile_ids))"];
        }

        echo "<tr class='tab_bg_1'>";
        echo "<td><label for='users_id_analyst'>Analista Responsável <span style='color:red;'>*</span></label></td>";
        echo "<td>";
        // Remove 'display_emptychoice' e adiciona required para obrigatoriedade nativa HTML (tenta, mas GLPI às vezes precisa de server-side)
        $user_options['display_emptychoice'] = true; // Mantém para forçar seleção
        $user_options['required'] = true;
        User::dropdown($user_options);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td><label for='date_start'>Data Início</label></td>";
        echo "<td>";
        Html::showDateField('date_start', ['value' => $this->fields['date_start']]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td><label for='date_end'>Data Fim</label></td>";
        echo "<td>";
        Html::showDateField('date_end', ['value' => $this->fields['date_end']]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1' id='row_observacoes' style='display:".($this->fields['status'] == 4 ? "table-row" : "none").";'>";
        echo "<td><label for='observacoes'>Observações <span style='color:red;'>*</span></label></td>";
        echo "<td>";
        echo "<textarea name='observacoes' id='observacoes' class='form-control' style='width:100%; height:100px;'>" . $this->fields['observacoes'] . "</textarea>";
        echo "</td>";
        echo "</tr>";

        echo "<script>
        function checkStatusOptante(val) {
           if (val == 4) {
              document.getElementById('row_observacoes').style.display = 'table-row';
              document.getElementById('observacoes').setAttribute('required', 'required');
           } else {
              document.getElementById('row_observacoes').style.display = 'none';
              document.getElementById('observacoes').removeAttribute('required');
           }
        }
        // Run on load
        checkStatusOptante(" . (int)$this->fields['status'] . ");
        </script>";

        echo "<tr class='tab_bg_2'>";
        echo "<td colspan='2' class='center'>";
        echo "<input type='hidden' name='id' value='".$this->fields['id']."'>";
        echo "<input type='hidden' name='plugin_taskmaster_implementations_id' value='".$this->fields['plugin_taskmaster_implementations_id']."'>";
        echo "<input type='hidden' name='plugin_taskmaster_tasks_id' value='".$this->fields['plugin_taskmaster_tasks_id']."'>";
        echo "<input type='submit' name='update' value='" . _sx('button', 'Save') . "' class='submit'>";
        echo "</table>";
        echo "</div>";
        Html::closeForm();
        return true;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        if ($item->getType() == 'PluginTaskmasterImplementationTask') {
            return [
                1 => __('Histórico')
            ];
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
        if ($item->getType() == 'PluginTaskmasterImplementationTask') {
            switch ($tabnum) {
                case 1:
                    Log::showForItem($item);
                    break;
            }
        }
        return true;
    }

    public function prepareInputForUpdate($input) {
        if (empty($input['users_id_analyst']) || $input['users_id_analyst'] <= 0) {
            Session::addMessageAfterRedirect("Analista Responsável é obrigatório.", false, ERROR);
            return false;
        }
        if (isset($input['status']) && $input['status'] == 4) {
            if (empty($input['observacoes'])) {
                Session::addMessageAfterRedirect("Observações são obrigatórias para o status 'Não optante'.", false, ERROR);
                return false;
            }
        }
        return $input;
    }
}
