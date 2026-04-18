<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}


class PluginTaskmasterSubtask extends CommonDBTM {
    static $rightname = 'plugin_taskmaster_module';
    public $dohistory = true;

    static function getTypeName($nb = 0) {
        return _n('Subtarefa', 'Subtarefas', $nb, 'taskmaster');
    }

    static function getIcon() {
        return "fas fa-list-ol";
    }

    static function canCreate() {
        return Session::haveRight('plugin_taskmaster_module', CREATE);
    }
    
    static function canView() {
        return Session::haveRight('plugin_taskmaster_module', READ);
    }
    
    static function canUpdate() {
        return Session::haveRight('plugin_taskmaster_module', UPDATE);
    }
    
    static function canDelete() {
        return Session::haveRight('plugin_taskmaster_module', PURGE);
    }

    public function rawSearchOptions() {
        $tab = [];

        $tab[] = [
            'id'                 => 'common',
            'name'               => __('Characteristics')
        ];

        $tab[] = [
            'id'                 => '1',
            'table'              => $this->getTable(),
            'field'              => 'name',
            'name'               => __('Nome'),
            'datatype'           => 'itemlink',
            'massiveaction'      => false
        ];
        
        $tab[] = [
            'id'                 => '2',
            'table'              => PluginTaskmasterTask::getTable(),
            'field'              => 'name',
            'name'               => PluginTaskmasterTask::getTypeName(1),
            'datatype'           => 'dropdown',
            'massiveaction'      => false
        ];

        return $tab;
    }


    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        if ($item->getType() == 'PluginTaskmasterTask') {
            return [
                1 => PluginTaskmasterSubtask::getTypeName(2)
            ];
        }
        if ($item->getType() == 'PluginTaskmasterSubtask') {
            return [
                1 => __('Histórico')
            ];
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
        if ($item->getType() == 'PluginTaskmasterTask') {
            switch ($tabnum) {
                case 1:
                    static::showForTask($item);
                    break;
            }
        }
        if ($item->getType() == 'PluginTaskmasterSubtask') {
            switch ($tabnum) {
                case 1:
                    Log::showForItem($item);
                    break;
            }
        }
        return true;
    }

    function showForm($id, array $options = []) {
        $this->initForm($id, $options);
        $this->showFormHeader($options);

        // Tarefa Pai
        echo "<tr class='tab_bg_1'>";
        echo "<td>Tarefa</td>";
        echo "<td>";
        if ($id > 0 || isset($options['plugin_taskmaster_tasks_id'])) {
            $tid = $id > 0 ? $this->fields['plugin_taskmaster_tasks_id'] : $options['plugin_taskmaster_tasks_id'];
            $task = new PluginTaskmasterTask();
            if ($task->getFromDB($tid)) {
                echo $task->fields['name'];
                echo "<input type='hidden' name='plugin_taskmaster_tasks_id' value='$tid'>";
            } else {
                Dropdown::show('PluginTaskmasterTask', ['name' => 'plugin_taskmaster_tasks_id', 'value' => $tid]);
            }
        } else {
            Dropdown::show('PluginTaskmasterTask', ['name' => 'plugin_taskmaster_tasks_id', 'value' => $this->fields['plugin_taskmaster_tasks_id']]);
        }
        echo "</td></tr>";

        // Nome
        echo "<tr class='tab_bg_1'>";
        echo "<td>Nome <span style='color:red;'>*</span></td>";
        echo "<td><input type='text' name='name' value='".Html::cleanInputText($this->fields['name'])."' required class='form-control' style='width: 300px;'></td>";
        echo "</tr>";

        // Status
        $is_active_value = $this->isNewItem() ? 1 : $this->fields['is_active'];
        echo "<tr class='tab_bg_1'>";
        echo "<td>Status Ativo <span style='color:red;'>*</span></td>";
        echo "<td>";
        Dropdown::showYesNo('is_active', $is_active_value);
        echo "</td></tr>";

        $options['add_cancel'] = true;
        $this->showFormButtons($options);

        return true;
    }

    static function showForTask(PluginTaskmasterTask $task) {
        global $DB;
        $id = $task->fields['id'];

        echo "<div class='center'>";
        echo "<table class='tab_cadre_fixehov'>";
        echo "<tr><th colspan='2'>Subtarefas da Tarefa</th></tr>";
        echo "<tr><th>Nome</th><th>Ações</th></tr>";
        
        $req = $DB->request('glpi_plugin_taskmaster_subtasks', ['plugin_taskmaster_tasks_id' => $id]);
        foreach ($req as $row) {
            echo "<tr>";
            echo "<td><a href='".PluginTaskmasterSubtask::getFormURL()."?id=".$row['id']."'>".$row['name']."</a></td>";
            echo "<td>";
            Html::showSimpleForm(PluginTaskmasterSubtask::getFormURL(), 'purge', __('Excluir'), ['id' => $row['id']], "", '', __('Confirm the final deletion?'));
            echo "</td>";
            echo "</tr>";
        }
        
        echo "<tr><td colspan='2' class='center'>";
        echo "<a class='vsubmit' href='".PluginTaskmasterSubtask::getFormURL()."?plugin_taskmaster_tasks_id=$id'>Adicionar Nova Subtarefa</a>";
        echo "</td></tr>";
        echo "</table></div>";
    }

    public function canPurgeItem() {
        global $DB;
        $id = $this->fields['id'];

        // Bloquear se houver vínculo com implantações
        $reqImpl = $DB->request('glpi_plugin_taskmaster_implementationsubtasks', ['plugin_taskmaster_subtasks_id' => $id]);
        if (count($reqImpl) > 0) {
            Session::addMessageAfterRedirect(
                "Não é possível excluir uma subtarefa que já está vinculada a uma implantação.",
                false,
                ERROR
            );
            return false;
        }

        return true;
    }
}
