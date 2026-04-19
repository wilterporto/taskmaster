<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginTaskmasterImplementationTask extends CommonDBTM {
    static $rightname = 'plugin_taskmaster_implementation';
    public $dohistory = true;

    static function getTypeName($nb = 0) {
        return _n('Tarefa da Implantação', 'Tarefas da Implantação', $nb, 'taskmaster');
    }

    static function canView() {
        return Session::haveRight(self::$rightname, READ);
    }

    static function canUpdate() {
        return Session::haveRight(self::$rightname, UPDATE);
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

        echo "<tr class='tab_bg_1'>";
        echo "<td><label for='users_id_analyst'>Analista Responsável <span style='color:red;'>*</span></label></td>";
        echo "<td>";
        User::dropdown([
            'name'                => 'users_id_analyst',
            'value'               => $this->fields['users_id_analyst'],
            'display_emptychoice' => true,
            'required'            => true
        ]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1' id='row_date_start'>";
        echo "<td><label for='date_start'>Data Início</label></td>";
        echo "<td>";
        Html::showDateField('date_start', ['value' => $this->fields['date_start']]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1' id='row_date_end'>";
        echo "<td><label for='date_end'>Data Fim</label></td>";
        echo "<td>";
        Html::showDateField('date_end', ['value' => $this->fields['date_end']]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1' id='row_observacoes'>";
        echo "<td><label for='observacoes'>Observações <span id='asterisk_obs' style='color:red; display:none;'>*</span></label></td>";
        echo "<td>";
        echo "<textarea name='observacoes' id='observacoes' class='form-control' style='width:100%; height:100px;'>" . $this->fields['observacoes'] . "</textarea>";
        echo "</td>";
        echo "</tr>";

        echo "<script>
        window.checkStatusOptante = function(val) {
           var rowStart = document.getElementById('row_date_start');
           var rowEnd = document.getElementById('row_date_end');
           var obsField = document.getElementById('observacoes');
           var asterisk = document.getElementById('asterisk_obs');

           if (val == 4) {
              if (rowStart) rowStart.style.display = 'none';
              if (rowEnd) rowEnd.style.display = 'none';
              if (obsField) obsField.setAttribute('required', 'required');
              if (asterisk) asterisk.style.display = 'inline';
           } else {
              if (rowStart) rowStart.style.display = '';
              if (rowEnd) rowEnd.style.display = '';
              if (obsField) obsField.removeAttribute('required');
              if (asterisk) asterisk.style.display = 'none';
           }
        };
        checkStatusOptante(" . (int)$this->fields['status'] . ");
        </script>";

        echo "<tr class='tab_bg_2'>";
        echo "<td colspan='2' class='center'>";
        echo "<input type='hidden' name='id' value='".$this->fields['id']."'>";
        echo "<input type='hidden' name='plugin_taskmaster_implementations_id' value='".$this->fields['plugin_taskmaster_implementations_id']."'>";
        echo "<input type='hidden' name='plugin_taskmaster_tasks_id' value='".$this->fields['plugin_taskmaster_tasks_id']."'>";
        echo "<input type='submit' name='update' value='" . _sx('button', 'Save') . "' class='btn btn-primary submit'>";
        echo "</td>";
        echo "</tr>";
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
