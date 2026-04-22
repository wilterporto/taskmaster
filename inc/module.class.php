<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginTaskmasterModule extends CommonDBTM {
    static $rightname = 'plugin_taskmaster_module';
    public $dohistory = true;

    static function getTypeName($nb = 0) {
        return _n('Módulo de Implantação', 'Módulos de Implantação', $nb, 'taskmaster');
    }

    static function getIcon() {
        return "fas fa-cubes";
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
            'table'              => $this->getTable(),
            'field'              => 'id',
            'name'               => __('ID'),
            'datatype'           => 'number',
            'massiveaction'      => false
        ];

        return $tab;
    }

    function showForm($id, array $options = []) {
        $this->initForm($id, $options);
        $this->showFormHeader($options);

        echo "<tr class='tab_bg_1'>";
        echo "<td><label for='name'>Nome do Módulo <span style='color:red;'>*</span></label></td>";
        echo "<td>";
        echo "<input type='text' name='name' id='name' value='".Html::cleanInputText($this->fields['name'])."' required class='form-control' style='width:100%; max-width: 400px;'>";
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td><label for='training_hours'>Tempo Estimado para Treinamento (horas) <span style='color:red;'>*</span></label></td>";
        echo "<td>";
        echo "<input type='number' name='training_hours' id='training_hours' value='".Html::cleanInputText($this->fields['training_hours'])."' required step='0.01' min='0' class='form-control' style='width:100%; max-width: 150px;'>";
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td colspan='2' class='center'>";
        if ($id > 0) {
            echo "<input type='hidden' name='id' value='$id'>";
            echo "<input type='submit' name='update' value='"._sx('button', 'Save')."' class='submit'>";
        } else {
            echo "<input type='submit' name='add' value='"._sx('button', 'Add')."' class='submit'>";
        }
        echo "&nbsp;<a href='".static::getSearchURL()."' class='vsubmit' style='background-color: #6c757d; color: white; border: none; padding: 5px 15px; text-decoration: none; border-radius: 3px; margin-left:10px; display: inline-block;'>" . __('Cancelar', 'taskmaster') . "</a>";
        if ($id > 0 && $this->canDelete()) {
            echo "&nbsp;<input type='submit' name='purge' value='"._sx('button', 'Delete')."' class='submit'>";
        }
        echo "</td>";
        echo "</tr>";
        echo "</table>";
        echo "</div>";
        Html::closeForm();
        return true;
    }

    static function showList() {
        global $DB;

        if (self::canCreate()) {
            echo "<div class='center' style='margin-bottom: 20px;'>";
            echo "<a href='" . self::getFormURL() . "' class='btn btn-primary mb-2' style='color:white; padding: 10px 20px; font-weight: bold;'>";
            echo "<i class='fas fa-plus-circle' style='margin-right:8px;'></i>Adicionar Novo Módulo";
            echo "</a>";
            echo "</div>";
        }

        $limit = 30;
        $start = isset($_GET['start']) ? (int)$_GET['start'] : 0;
        $t_modulo = self::getTable();
        $t_tarefa = PluginTaskmasterTask::getTable();
        $t_subtarefa = PluginTaskmasterSubtask::getTable();

        $total_req = $DB->request(['COUNT' => 'c', 'FROM' => $t_modulo]);
        $total = 0;
        if ($row = $total_req->current()) {
            $total = $row['c'];
        }

        Html::printPager($start, $total, $_SERVER['PHP_SELF'], '', false);

        echo "<div class='center'>";
        echo "<table class='tab_cadre_fixehov'>";
        echo "<tr class='tab_bg_2'>";
        echo "<th width='40%'>" . __('Nome do Módulo') . "</th>";
        echo "<th width='150' class='center'>Qtd. Tarefas</th>";
        echo "<th width='150' class='center'>Qtd. Subtarefas</th>";
        echo "<th width='50' class='center'>Ações</th>"; 
        echo "</tr>";

        $req = $DB->request([
            'FROM'  => $t_modulo,
            'START' => $start,
            'LIMIT' => $limit,
            'ORDER' => 'name ASC'
        ]);

        foreach ($req as $modulo) {
            $id = $modulo['id'];
            $nome = $modulo['name'];

            $tarefas_req = $DB->request(['FROM' => $t_tarefa, 'WHERE' => ['plugin_taskmaster_modules_id' => $id]]);
            $tarefas = iterator_to_array($tarefas_req);
            $qtd_tarefas = count($tarefas);

            $qtd_subtarefas = 0;
            if ($qtd_tarefas > 0) {
                $ids_tarefas = array_column($tarefas, 'id');
                $sub_req = $DB->request(['COUNT' => 'c', 'FROM' => $t_subtarefa, 'WHERE' => ['plugin_taskmaster_tasks_id' => $ids_tarefas]]);
                if ($sub_row = $sub_req->current()) {
                    $qtd_subtarefas = $sub_row['c'];
                }
            }

            echo "<tr class='tab_bg_1'>";
            echo "<td><a href='".self::getFormURLWithID($id)."'>".$nome."</a></td>";
            echo "<td class='center'>".$qtd_tarefas."</td>";
            echo "<td class='center'>".$qtd_subtarefas."</td>";
            echo "<td class='center' style='display: flex; justify-content: center; align-items: center; gap: 5px;'>";
            if (self::canDelete()) {
                Html::showSimpleForm(self::getFormURL(), 'purge', __('Excluir'), ['id' => $id], "", '', __('Confirm the final deletion?'));
            }
            echo "<button class='btn btn-sm btn-secondary toggle-details-btn' data-target='".$id."' style='padding: 2px 8px; font-size: 11px;' title='Expandir/Recolher'>";
            echo "<i class='fas fa-chevron-down' id='icon-".$id."'></i>";
            echo "</button>";
            echo "</td>";
            echo "</tr>";

            echo "<tr id='detail-".$id."' style='display:none;' class='tab_bg_2'>";
            echo "<td colspan='4' style='padding:20px; border-top: 1px solid #ddd; background: #ffffff;'>";

            if ($qtd_tarefas > 0) {
                echo "<div style='font-size: 13px; margin-bottom: 10px;'><strong>Estrutura do Módulo:</strong></div>";
                echo "<ul style='list-style: none; padding-left: 0; margin: 0; font-size: 13px;'>";
                foreach ($tarefas as $tarefa) {
                    echo "<li style='margin-bottom: 12px;'>";
                    echo "<div style='margin-bottom: 4px;'><i class='fas fa-list-ul' style='color:#007bff; margin-right:6px;'></i> <b><a href='".PluginTaskmasterTask::getFormURLWithID($tarefa['id'])."'>".$tarefa['name']."</a></b></div>";
                    $subtarefas_req = $DB->request(['FROM' => $t_subtarefa, 'WHERE' => ['plugin_taskmaster_tasks_id' => $tarefa['id']]]);
                    $subtarefas = iterator_to_array($subtarefas_req);
                    if (count($subtarefas) > 0) {
                        echo "<ul style='list-style: none; padding-left: 20px; border-left: 2px solid #e0e0e0; margin-left: 6px; margin-top: 5px; margin-bottom: 5px;'>";
                        foreach ($subtarefas as $subtarefa) {
                            echo "<li style='margin-bottom: 4px;'><i class='fas fa-level-up-alt fa-rotate-90' style='color:#999; margin-right:6px; font-size: 11px;'></i> <a href='".PluginTaskmasterSubtask::getFormURLWithID($subtarefa['id'])."' style='color: #555;'>".$subtarefa['name']."</a></li>";
                        }
                        echo "</ul>";
                    } else {
                        echo "<div style='color:#999; font-size: 11px; margin-left: 22px;'>[Sem subtarefas]</div>";
                    }
                    echo "</li>";
                }
                echo "</ul>";
            } else {
                echo "<div style='color:#999; font-size: 13px;'><i class='fas fa-info-circle'></i> Nenhuma tarefa vinculada a este módulo.</div>";
            }

            echo "</td>";
            echo "</tr>";
        }

        echo "</table>";
        Html::printPager($start, $total, $_SERVER['PHP_SELF'], '', true);
        echo "</div>";

        echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            var buttons = document.querySelectorAll('.toggle-details-btn');
            buttons.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var targetId = this.getAttribute('data-target');
                    var targetRow = document.getElementById('detail-' + targetId);
                    var targetIcon = document.getElementById('icon-' + targetId);
                    
                    if (targetRow.style.display === 'none') {
                        targetRow.style.display = 'table-row';
                        targetIcon.classList.remove('fa-chevron-down');
                        targetIcon.classList.add('fa-chevron-up');
                    } else {
                        targetRow.style.display = 'none';
                        targetIcon.classList.remove('fa-chevron-up');
                        targetIcon.classList.add('fa-chevron-down');
                    }
                });
            });
        });
        </script>";
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        if ($item->getType() == 'PluginTaskmasterModule') {
            return [
                1 => __('Histórico')
            ];
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
        if ($item->getType() == 'PluginTaskmasterModule') {
            switch ($tabnum) {
                case 1:
                    Log::showForItem($item);
                    break;
            }
        }
        return true;
    }

    public function canPurgeItem() {
        global $DB;
        $id = $this->fields['id'];

        // Bloquear se houver tarefas vinculadas
        $reqTask = $DB->request('glpi_plugin_taskmaster_tasks', ['plugin_taskmaster_modules_id' => $id]);
        if (count($reqTask) > 0) {
            Session::addMessageAfterRedirect(
                "Não é possível excluir um módulo que possui tarefas vinculadas.",
                false,
                ERROR
            );
            return false;
        }

        return true;
    }
}
