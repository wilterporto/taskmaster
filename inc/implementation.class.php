<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginTaskmasterImplementation extends CommonDBTM {
    static $rightname = 'plugin_taskmaster_impl';
    public $dohistory = true;

    static function getTypeName($nb = 0) {
        return _n('Implantação', 'Implantações', $nb, 'taskmaster');
    }

    static function getIcon() {
        return "fas fa-truck-loading";
    }

    static function canCreate() {
        return Session::haveRight('plugin_taskmaster_impl', CREATE) || Session::haveRight('plugin_taskmaster_manage', CREATE);
    }
    
    static function canView() {
        return Session::haveRight('plugin_taskmaster_impl', READ) || Session::haveRight('plugin_taskmaster_manage', READ);
    }
    
    static function canUpdate() {
        return Session::haveRight('plugin_taskmaster_impl', UPDATE) || Session::haveRight('plugin_taskmaster_manage', UPDATE);
    }
    
    static function canDelete() {
        return Session::haveRight('plugin_taskmaster_impl', PURGE) || Session::haveRight('plugin_taskmaster_manage', PURGE);
    }

    public function canCreateItem() {
        return self::canCreate();
    }

    public function canUpdateItem() {
        return self::canUpdate();
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
            'id'                 => '80',
            'table'              => 'glpi_entities',
            'field'              => 'completename',
            'name'               => __('Entity'),
            'datatype'           => 'dropdown'
        ];

        $tab[] = [
            'id'                 => '2',
            'table'              => 'glpi_users',
            'field'              => 'name',
            'linkfield'          => 'users_id_responsible',
            'name'               => __('Analista Responsável'),
            'datatype'           => 'dropdown'
        ];

        $tab[] = [
            'id'                 => '3',
            'table'              => $this->getTable(),
            'field'              => 'date_begin',
            'name'               => __('Data de Início'),
            'datatype'           => 'datetime'
        ];

        return $tab;
    }

    function showForm($id, array $options = []) {
        global $DB;
        $this->initForm($id, $options);
        $options['canedit'] = true;
        $this->showFormHeader($options);

        echo "<tr class='tab_bg_1'>";
        echo "<td><label for='name'>Nome da Implantação <span style='color:red;'>*</span></label></td>";
        echo "<td>";
        echo "<input type='text' name='name' id='name' value='".Html::cleanInputText($this->fields['name'])."' required class='form-control' style='width:100%; max-width: 400px;'>";
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td><label for='entities_id'>Entidade <span style='color:red;'>*</span></label></td>";
        echo "<td>";
        Entity::dropdown([
            'name'      => 'entities_id', 
            'value'     => $this->fields['entities_id'], 
            'required'  => true,
            'condition' => ['entities_id' => 0]
        ]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td><label for='users_id_responsible'>Analista Responsável <span style='color:red;'>*</span></label></td>";
        echo "<td>";
        User::dropdown(['name' => 'users_id_responsible', 'value' => $this->fields['users_id_responsible'], 'entity' => $this->fields['entities_id'], 'required' => true]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td><label for='date_begin'>Data de Início <span style='color:red;'>*</span></label></td>";
        echo "<td>";
        Html::showDateTimeField('date_begin', ['value' => $this->fields['date_begin'], 'required' => true]);
        echo "</td>";
        echo "</tr>";
        if ($this->isNewItem()) {
            echo "<tr class='tab_bg_1'>";
            echo "<td><label>Módulos a Implantar <span style='color:red;'>*</span></label></td>";
            echo "<td>";
            
            // Renderização manual para evitar crash no GLPI usando a função nativa com multiple
            echo "<select name='_modules[]' multiple='multiple' class='form-control' style='width: 100%; max-width: 400px;' required>";
            $reqModulos = $DB->request('glpi_plugin_taskmaster_modules');
            foreach ($reqModulos as $mod) {
                echo "<option value='".$mod['id']."'>".Html::cleanInputText($mod['name'])."</option>";
            }
            echo "</select>";
            echo "<div style='font-size: 11px; color: #666; margin-top: 4px;'><i class='fas fa-info-circle'></i> Segure CTRL (ou CMD no Mac) para selecionar mais de um módulo.</div>";
            
            echo "</td>";
            echo "</tr>";
        }
        if ($this->isNewItem()) {
            echo "<tr class='tab_bg_2'>";
            echo "<td colspan='2' class='center'>";
            echo "<input type='hidden' name='_glpi_csrf_token' value='".Session::getNewCSRFToken()."'>";
            echo "<input type='submit' name='add' value='Adicionar' class='btn btn-primary submit' style='padding: 8px 20px;'>";
            echo "</td>";
            echo "</tr>";
            
            // É crítico fechar as tags abertas pelo showFormHeader();
            echo "</table>";
            echo "</div>";
            Html::closeForm();
        } else {
            $this->showFormButtons($options);
        }
        
        return true;
    }

    public function prepareInputForAdd($input) {
        if (empty($input['entities_id'])) {
            Session::addMessageAfterRedirect("Entidade é obrigatória.", false, ERROR);
            return false;
        }
        if (empty($input['users_id_responsible'])) {
            Session::addMessageAfterRedirect("Analista responsável é obrigatório.", false, ERROR);
            return false;
        }
        if (empty($input['date_begin'])) {
            Session::addMessageAfterRedirect("Data de início é obrigatória.", false, ERROR);
            return false;
        }
        if (empty($input['_modules']) || !is_array($input['_modules'])) {
            Session::addMessageAfterRedirect("Selecione ao menos um módulo.", false, ERROR);
            return false;
        }
        return $input;
    }

    public function post_addItem() {
        $impl_id = $this->fields['id'];
        $modules = $this->input['_modules'] ?? [];
        
        if (is_array($modules) && count($modules) > 0) {
            foreach ($modules as $module_id) {
                $this->addModule($impl_id, $module_id);
            }
        }
    }

    public function addModule($impl_id, $module_id) {
        global $DB;
        
        // Evita a duplicidade de módulos na mesma implantação
        $reqCheck = $DB->request('glpi_plugin_taskmaster_implementations_modules', [
            'plugin_taskmaster_implementations_id' => $impl_id,
            'plugin_taskmaster_modules_id'         => $module_id
        ]);
        if (count($reqCheck) > 0) {
            return false;
        }

        // Adiciona na tabela de ligação 
        $DB->insert('glpi_plugin_taskmaster_implementations_modules', [
            'plugin_taskmaster_implementations_id' => $impl_id,
            'plugin_taskmaster_modules_id' => $module_id
        ]);

        // Carrega tarefas do módulo e insere (apenas ativas)
        $reqTasks = $DB->request('glpi_plugin_taskmaster_tasks', [
            'plugin_taskmaster_modules_id' => $module_id,
            'is_active'                    => 1
        ]);
        foreach ($reqTasks as $task) {
            $DB->insert('glpi_plugin_taskmaster_implementationtasks', [
                'plugin_taskmaster_implementations_id' => $impl_id,
                'plugin_taskmaster_tasks_id' => $task['id'],
                'status' => 0 // Não iniciado
            ]);
            $implTaskId = $DB->insertId();

            // Carrega subtarefas e insere (apenas ativas)
            $reqSub = $DB->request('glpi_plugin_taskmaster_subtasks', [
                'plugin_taskmaster_tasks_id' => $task['id'],
                'is_active'                  => 1
            ]);
            foreach ($reqSub as $subtask) {
                $DB->insert('glpi_plugin_taskmaster_implementationsubtasks', [
                    'plugin_taskmaster_implementationtasks_id' => $implTaskId,
                    'plugin_taskmaster_subtasks_id' => $subtask['id'],
                    'status' => 0 // Não iniciado
                ]);
            }
        }
        
        return true;
    }

    public function removeModule($impl_id, $module_id) {
        global $DB;

        // Pega as tarefas da implantação
        $reqTasks = $DB->request('glpi_plugin_taskmaster_implementationtasks', [
            'plugin_taskmaster_implementations_id' => $impl_id
        ]);
        
        foreach ($reqTasks as $t) {
            $taskObj = new PluginTaskmasterTask();
            // Verifica se a tarefa origina-se do modulo a deletar
            if ($taskObj->getFromDB($t['plugin_taskmaster_tasks_id']) && $taskObj->fields['plugin_taskmaster_modules_id'] == $module_id) {
                // Remove as subtarefas dessa tarefa dentro dessa implantação
                $DB->delete('glpi_plugin_taskmaster_implementationsubtasks', [
                    'plugin_taskmaster_implementationtasks_id' => $t['id']
                ]);
                // Remove a tarefa dessa implantação
                $DB->delete('glpi_plugin_taskmaster_implementationtasks', [
                    'id' => $t['id']
                ]);
            }
        }

        // Finalmente, remove a ligação do módulo
        $DB->delete('glpi_plugin_taskmaster_implementations_modules', [
            'plugin_taskmaster_implementations_id' => $impl_id,
            'plugin_taskmaster_modules_id' => $module_id
        ]);

        return true;
    }

    static function showList() {
        global $DB;

        $limit = 30;
        $start = isset($_GET['start']) ? (int)$_GET['start'] : 0;
        $t_impl = self::getTable();

        $total_req = $DB->request(['COUNT' => 'c', 'FROM' => $t_impl]);
        $total = 0;
        if ($row = $total_req->current()) {
            $total = $row['c'];
        }

        Html::printPager($start, $total, $_SERVER['PHP_SELF'], '');

        echo "<div class='center'>";
        echo "<table class='tab_cadre_fixehov'>";
        echo "<tr class='tab_bg_2'>";
        echo "<th width='40%'>" . __('Nome da Implantação') . "</th>";
        echo "<th class='center'>" . __('Entidade') . "</th>";
        echo "<th width='250' class='center'>" . __('Progresso') . "</th>";
        echo "</tr>";

        $req = $DB->request([
            'FROM'  => $t_impl,
            'START' => $start,
            'LIMIT' => $limit,
            'ORDER' => 'id DESC'
        ]);

        foreach ($req as $impl) {
            $id = $impl['id'];
            
            // Progress Calculation for this row
            $totalItems = 0;
            $doneItems = 0;
            $tasksReq = $DB->request(['FROM' => 'glpi_plugin_taskmaster_implementationtasks', 'WHERE' => ['plugin_taskmaster_implementations_id' => $id]]);
            foreach ($tasksReq as $treq) {
                $totalItems++;
                if ($treq['status'] == 3 || $treq['status'] == 4) $doneItems++;
                $subReq = $DB->request(['FROM' => 'glpi_plugin_taskmaster_implementationsubtasks', 'WHERE' => ['plugin_taskmaster_implementationtasks_id' => $treq['id']]]);
                foreach ($subReq as $sreq) {
                    $totalItems++;
                    if ($sreq['status'] == 3 || $sreq['status'] == 4) $doneItems++;
                }
            }
            $progress = $totalItems > 0 ? round(($doneItems / $totalItems) * 100, 2) : 0;

            echo "<tr class='tab_bg_1'>";
            echo "<td><a href='".self::getFormURLWithID($id)."'>".$impl['name']."</a></td>";
            
            $ent_full_name = Dropdown::getDropdownName('glpi_entities', $impl['entities_id']);
            // O GLPI costuma tratar '<' e '>' convertendo para caracteres HTML (&gt;) ou afins na renderização/banco.
            // Para garantir que o explode/regex funcione, forçamos o decode antes de separar.
            $decoded_name = html_entity_decode($ent_full_name, ENT_QUOTES, 'UTF-8');
            $ent_parts = preg_split('/\s*>\s*/', $decoded_name);
            
            if (count($ent_parts) > 1) {
                array_shift($ent_parts); // Remove entidade raiz
                $ent_name = implode(' > ', $ent_parts);
            } else {
                $ent_name = $decoded_name;
            }
            
            echo "<td class='center'>".Html::cleanInputText($ent_name)."</td>";
            
            // Progress bar
            $color = '#d9534f';
            if ($progress == 100) $color = '#5cb85c';
            else if ($progress >= 50) $color = '#5bc0de';
            else if ($progress > 0) $color = '#f0ad4e';

            echo "<td width='250' style='padding: 10px;'>";
            echo "<div style='width: 100%; border: 1px solid #ccc; background-color: #f5f5f5; border-radius: 4px; position:relative;'>";
            echo "<div style='width: ".$progress."%; background-color: ".$color."; height: 20px; border-radius: 3px;'></div>";
            echo "<div style='position:absolute; width:100%; top:0; text-align:center; color:".($progress>50?"white":"black")."; font-weight:bold; line-height:20px; font-size: 11px;'>".$progress."%</div>";
            echo "</div>";
            echo "</td>";
            echo "</tr>";
        }

        if ($total == 0) {
            echo "<tr><td colspan='3' class='center'>Nenhuma implantação cadastrada</td></tr>";
        }

        echo "</table>";
        Html::printPager($start, $total, $_SERVER['PHP_SELF'], '');
        echo "</div>";
    }

    
    public static function getStatusName($status) {
        $statuses = [
            0 => 'Não iniciado',
            1 => 'Planejado',
            2 => 'Em andamento',
            3 => 'Concluído',
            4 => 'Não optante'
        ];
        return $statuses[$status] ?? 'Desconhecido';
    }

    public static function showTracking(PluginTaskmasterImplementation $item) {
        global $DB, $CFG_GLPI;
        $id = $item->fields['id'];
        
        // Progress Calculation
        $totalItems = 0;
        $doneItems = 0;
        $statusCounts = [0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0];
        
        // Fetch tasks
        $tasksReq = $DB->request(['FROM' => 'glpi_plugin_taskmaster_implementationtasks', 'WHERE' => ['plugin_taskmaster_implementations_id' => $id]]);
        $tasks = [];
        
        foreach ($tasksReq as $treq) {
            $totalItems++;
            if ($treq['status'] == 3 || $treq['status'] == 4) $doneItems++;
            $statusCounts[$treq['status']]++;
            
            $subReq = $DB->request(['FROM' => 'glpi_plugin_taskmaster_implementationsubtasks', 'WHERE' => ['plugin_taskmaster_implementationtasks_id' => $treq['id']]]);
            $subtasks = [];
            foreach ($subReq as $sreq) {
                $totalItems++;
                if ($sreq['status'] == 3 || $sreq['status'] == 4) $doneItems++;
                $statusCounts[$sreq['status']]++;
                $subtasks[] = $sreq;
            }
            $treq['subtasks'] = $subtasks;
            $tasks[] = $treq;
        }
        
        $progress = $totalItems > 0 ? round(($doneItems / $totalItems) * 100, 2) : 0;

        // Botão de impressão do relatório
        $reportUrl = $CFG_GLPI['root_doc'] . '/plugins/taskmaster/front/implementation.report.php?id=' . $id;
        echo "<div style='text-align:right; margin-bottom:10px;'>";
        echo "<a href='" . $reportUrl . "' target='_blank'
                style='display:inline-flex; align-items:center; gap:6px; padding:7px 16px;
                       background:#1a237e; color:#fff; border-radius:6px; text-decoration:none;
                       font-size:13px; font-weight:600;'>
                🖨️ Imprimir Relatório
              </a>";
        echo "</div>";

        echo "<div class='center'>";
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr><th colspan='4'>Resumo da Implantação (" . $progress . "% Concluído)</th></tr>";
        
        $responsibleName = '';
        if ($item->fields['users_id_responsible'] > 0) {
            $user = new User();
            $user->getFromDB($item->fields['users_id_responsible']);
            $responsibleName = $user->getName();
        }
        $formattedDate = '';
        if (!empty($item->fields['date_begin'])) {
            $formattedDate = Html::convDateTime($item->fields['date_begin']);
        }

        echo "<tr>";
        echo "<td colspan='2'><strong>Analista Responsável:</strong> $responsibleName</td>";
        echo "<td colspan='2'><strong>Data de Início:</strong> $formattedDate</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<th>Não iniciado: {$statusCounts[0]}</th>";
        echo "<th>Planejado: {$statusCounts[1]}</th>";
        echo "<th>Em andamento: {$statusCounts[2]}</th>";
        echo "<th>Concluído/Não optante: " . ($statusCounts[3] + $statusCounts[4]) . "</th>";
        echo "</tr>";
        echo "</table><br>";

        // Busca módulos já incluídos (Tabela de ligação)
        $reqAddedMods = $DB->request('glpi_plugin_taskmaster_implementations_modules', ['plugin_taskmaster_implementations_id' => $id]);
        $addedModuleIds = [];
        foreach ($reqAddedMods as $am) {
            $addedModuleIds[] = $am['plugin_taskmaster_modules_id'];
        }

        // Reparação retroativa: Se a implantação antiga gerou tarefas mas perdeu o vínculo de módulo
        foreach ($tasks as $t) {
            $tObj = new PluginTaskmasterTask();
            if ($tObj->getFromDB($t['plugin_taskmaster_tasks_id'])) {
                $modId = $tObj->fields['plugin_taskmaster_modules_id'];
                if ($modId > 0 && !in_array($modId, $addedModuleIds)) {
                    $addedModuleIds[] = $modId;
                    // Repara o banco silenciosamente
                    $DB->insert('glpi_plugin_taskmaster_implementations_modules', [
                        'plugin_taskmaster_implementations_id' => $id,
                        'plugin_taskmaster_modules_id' => $modId
                    ]);
                }
            }
        }

        // Formulário para remover módulos em lote
        if (count($addedModuleIds) > 0) {
            echo "<form method='post' action='".$CFG_GLPI['root_doc']."/plugins/taskmaster/front/implementation.form.php'>";
            echo "<input type='hidden' name='id' value='$id'>";
            echo "<input type='hidden' name='_glpi_csrf_token' value='".Session::getNewCSRFToken()."'>";
            echo "<table class='tab_cadre_fixehov' style='margin-bottom: 20px;'>";
            echo "<tr><th colspan='2'>Módulos Vinculados na Implantação</th></tr>";
            echo "<tr><th width='40' class='center'>#</th><th>Nome do Módulo</th></tr>";
            foreach ($addedModuleIds as $mId) {
                $mod = new PluginTaskmasterModule();
                if ($mod->getFromDB($mId)) {
                    echo "<tr class='tab_bg_1'>";
                    echo "<td class='center'><input type='checkbox' name='delete_modules[]' value='$mId'></td>";
                    echo "<td>".$mod->fields['name']."</td>";
                    echo "</tr>";
                }
            }
            echo "<tr class='tab_bg_2'>";
            echo "<td colspan='2' class='center'>";
            echo "<input type='submit' name='remove_modules' value='Remover Módulos Selecionados' class='btn btn-danger' onclick='return confirm(\"Tem certeza que deseja remover os módulos selecionados e todas as suas tarefas desta implantação?\");' style='background-color:#d9534f; color:white; padding: 6px 12px; border:none; border-radius:3px;'>";
            echo "</td></tr>";
            echo "</table>";
            Html::closeForm();
        }

        // Formulário para adicionar mais módulos após a criação
        echo "<form method='post' action='".$CFG_GLPI['root_doc']."/plugins/taskmaster/front/implementation.form.php'>";
        echo "<input type='hidden' name='id' value='$id'>";
        echo "<input type='hidden' name='_glpi_csrf_token' value='".Session::getNewCSRFToken()."'>";
        echo "<table class='tab_cadre_fixe' style='margin-bottom: 20px;'>";
        echo "<tr><th colspan='2'>Adicionar Módulo Adicional</th></tr>";
        echo "<tr class='tab_bg_1'>";
        echo "<td class='center'>";
        echo "<select name='add_module_id' class='form-control' required>";
        echo "<option value=''>--- Selecione um módulo ---</option>";
        $reqMods = $DB->request('glpi_plugin_taskmaster_modules');
        $hasAvailable = false;
        foreach ($reqMods as $mod) {
            if (!in_array($mod['id'], $addedModuleIds)) {
                echo "<option value='".$mod['id']."'>".Html::cleanInputText($mod['name'])."</option>";
                $hasAvailable = true;
            }
        }
        echo "</select>";
        echo "</td>";
        echo "<td class='center' width='200'>";
        echo "<input type='submit' name='add_module' value='Adicionar Módulo' class='submit btn btn-primary' ".(!$hasAvailable ? "disabled" : "").">";
        echo "</td>";
        echo "</tr>";
        echo "</table>";
        Html::closeForm();

        // ---------------------------------------------------------------
        // Estrutura agrupada por módulo com percentual de conclusão
        // ---------------------------------------------------------------

        // Agrupa tasks por módulo
        $tasksByModule = [];
        foreach ($tasks as $task) {
            $tObj = new PluginTaskmasterTask();
            if ($tObj->getFromDB($task['plugin_taskmaster_tasks_id'])) {
                $moduleId = $tObj->fields['plugin_taskmaster_modules_id'];
                $task['_task_name'] = $tObj->fields['name'];
                $tasksByModule[$moduleId][] = $task;
            }
        }

        echo "<table class='tab_cadre_fixehov' style='width:100%;'>";

        foreach ($addedModuleIds as $mId) {
            $mod = new PluginTaskmasterModule();
            if (!$mod->getFromDB($mId)) continue;

            $moduleTasks   = $tasksByModule[$mId] ?? [];
            $modTotal      = 0;
            $modDone       = 0;

            foreach ($moduleTasks as $mt) {
                $modTotal++;
                if ($mt['status'] == 3 || $mt['status'] == 4) $modDone++;
                foreach ($mt['subtasks'] as $ms) {
                    $modTotal++;
                    if ($ms['status'] == 3 || $ms['status'] == 4) $modDone++;
                }
            }

            $modProgress = $modTotal > 0 ? round(($modDone / $modTotal) * 100, 2) : 0;

            // Cor da barra por faixa de progresso
            $barColor = '#d9534f';
            if ($modProgress == 100)      $barColor = '#5cb85c';
            elseif ($modProgress >= 50)   $barColor = '#5bc0de';
            elseif ($modProgress > 0)     $barColor = '#f0ad4e';
            $textColor = $modProgress > 50 ? 'white' : '#333';

            // Cabeçalho do módulo com barra de progresso
            echo "<tr style='background-color:#c8daf5;'>";
            echo "  <td colspan='4' style='padding:8px 10px;'>";
            echo "    <div style='display:flex; align-items:center; gap:12px;'>";
            echo "      <strong style='font-size:14px; white-space:nowrap;'><i class='fas fa-cube'></i> " . Html::cleanInputText($mod->fields['name']) . "</strong>";
            echo "      <div style='flex:1; position:relative; background:#e9ecef; border-radius:6px; height:22px; min-width:120px; max-width:340px; overflow:hidden;'>";
            echo "        <div style='width:{$modProgress}%; background:{$barColor}; height:100%; border-radius:6px; transition:width .4s;'></div>";
            echo "        <span style='position:absolute; top:0; left:0; width:100%; text-align:center; line-height:22px; font-size:12px; font-weight:bold; color:{$textColor};'>{$modProgress}%</span>";
            echo "      </div>";
            echo "      <span style='font-size:12px; color:#555; white-space:nowrap;'>{$modDone} / {$modTotal} " . ($modTotal == 1 ? "item" : "itens") . " concluído" . ($modDone == 1 ? "" : "s") . "</span>";
            echo "    </div>";
            echo "  </td>";
            echo "</tr>";

            // Cabeçalho das colunas do módulo
            echo "<tr style='background-color:#dde8f8;'>";
            echo "  <th style='padding-left:12px;'>Tarefa / Subtarefa</th>";
            echo "  <th>Status</th>";
            echo "  <th>Analista</th>";
            echo "  <th>Ações</th>";
            echo "</tr>";

            if (empty($moduleTasks)) {
                echo "<tr class='tab_bg_1'>";
                echo "  <td colspan='4' class='center' style='font-style:italic; color:#888;'>Nenhuma tarefa registrada para este módulo.</td>";
                echo "</tr>";
            } else {
                foreach ($moduleTasks as $task) {
                    $analystName = '';
                    if ($task['users_id_analyst'] > 0) {
                        $user = new User();
                        $user->getFromDB($task['users_id_analyst']);
                        $analystName = $user->getName();
                    }

                    echo "<tr style='background-color:#f0f4fc; font-weight:bold;'>";
                    echo "  <td style='padding-left:16px;'>" . Html::cleanInputText($task['_task_name']) . "</td>";
                    echo "  <td>" . self::getStatusName($task['status']) . "</td>";
                    echo "  <td>" . Html::cleanInputText($analystName) . "</td>";
                    echo "  <td><a href='".$CFG_GLPI['root_doc']."/plugins/taskmaster/front/implementationtask.form.php?id=".$task['id']."'>Editar</a></td>";
                    echo "</tr>";

                    foreach ($task['subtasks'] as $sub) {
                        $subObj = new PluginTaskmasterSubtask();
                        $subObj->getFromDB($sub['plugin_taskmaster_subtasks_id']);

                        $analystSubName = '';
                        if ($sub['users_id_analyst'] > 0) {
                            $userSub = new User();
                            $userSub->getFromDB($sub['users_id_analyst']);
                            $analystSubName = $userSub->getName();
                        }

                        echo "<tr class='tab_bg_1'>";
                        echo "  <td style='padding-left:40px;'>↳ " . Html::cleanInputText($subObj->fields['name']) . "</td>";
                        echo "  <td>" . self::getStatusName($sub['status']) . "</td>";
                        echo "  <td>" . Html::cleanInputText($analystSubName) . "</td>";
                        echo "  <td><a href='".$CFG_GLPI['root_doc']."/plugins/taskmaster/front/implementationsubtask.form.php?id=".$sub['id']."'>Editar</a></td>";
                        echo "</tr>";
                    }
                }
            }

            // Linha separadora entre módulos
            echo "<tr><td colspan='4' style='height:12px; background:transparent;'></td></tr>";
        }

        if (empty($addedModuleIds)) {
            echo "<tr><td colspan='4' class='center'>Nenhum módulo vinculado a esta implantação.</td></tr>";
        }

        echo "</table></div>";
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        if ($item->getType() == 'PluginTaskmasterImplementation') {
            return [
                1 => __('Acompanhamento', 'taskmaster'),
                2 => __('Histórico')
            ];
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
        if ($item->getType() == 'PluginTaskmasterImplementation') {
            switch ($tabnum) {
                case 1:
                    static::showTracking($item);
                    break;
                case 2:
                    Log::showForItem($item);
                    break;
            }
        }
        return true;
    }
}
