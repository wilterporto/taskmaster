<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginTaskmasterImplementationSubtask extends CommonDBTM {
    static $rightname = 'plugin_taskmaster_implementation';
    public $dohistory = true;

    static function getTypeName($nb = 0) {
        return _n('Subtarefa da Implantação', 'Subtarefas da Implantação', $nb, 'taskmaster');
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
        global $CFG_GLPI;
        $this->initForm($id, $options);
        $options['force_upload'] = true;
        $this->showFormHeader($options);
        
        // Script agressivo para garantir o enctype caso o showFormHeader falhe
        echo "<script>
            (function() {
                function fixEnctype() {
                    var forms = document.forms;
                    for (var i = 0; i < forms.length; i++) {
                        if (forms[i].innerHTML.indexOf('evidence_file') !== -1) {
                            forms[i].enctype = 'multipart/form-data';
                        }
                    }
                }
                fixEnctype();
                window.addEventListener('load', fixEnctype);
                setTimeout(fixEnctype, 500);
            })();
        </script>";

        $subtask = new PluginTaskmasterSubtask();
        $subtask->getFromDB($this->fields['plugin_taskmaster_subtasks_id']);
        
        echo "<tr class='tab_bg_1'>";
        echo "<th colspan='2'>Editar Subtarefa: " . $subtask->fields['name'] . "</th>";
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
            'required'            => true,
            'right'               => 'plugin_taskmaster_implementation',
            'entity'              => -1,
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
        Html::textarea([
            'name'             => 'observacoes',
            'value'            => htmlspecialchars_decode($this->fields['observacoes'] ?? ''),
            'id'               => 'observacoes',
            'width'            => '100%',
            'rows'             => 10
        ]);
        echo "</td>";
        echo "</tr>";

        // Campos de Evidência
        echo "<tr class='tab_bg_1' id='row_evidence_type' style='display:none;'>";
        echo "<td><label for='evidence_type'>Tipo de evidência <span style='color:red;'>*</span></label></td>";
        echo "<td>";
        Dropdown::showFromArray('evidence_type', [
            0 => '---',
            1 => 'Arquivo',
            2 => 'Link'
        ], [
            'value' => $this->fields['evidence_type'] ?? 0,
            'on_change' => 'checkEvidenceType(this.value)'
        ]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1' id='row_evidence_link' style='display:none;'>";
        echo "<td><label for='evidence_link'>Link da Evidência <span style='color:red;'>*</span></label></td>";
        echo "<td><input type='text' name='evidence_link' value='".(($this->fields['evidence_type'] ?? 0) == 2 ? ($this->fields['evidence_data'] ?? '') : '')."' style='width:100%'></td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1' id='row_evidence_file' style='display:none;'>";
        echo "<td><label for='evidence_file'>Arquivo de Evidência <span id='asterisk_file' style='color:red;'>*</span></label></td>";
        echo "<td>";
        if (($this->fields['evidence_type'] ?? 0) == 1 && !empty($this->fields['evidence_data'] ?? '')) {
            echo "<div style='margin-bottom:10px;'><strong>Arquivo atual:</strong> <a href='".$CFG_GLPI['root_doc']."/plugins/taskmaster/front/download.php?id=".$this->fields['id']."&type=subtask' class='btn btn-sm btn-info' target='_blank'><i class='fas fa-download'></i> Baixar: ".($this->fields['evidence_data'] ?? '')."</a></div>";
        }
        echo "<input type='file' name='evidence_file' id='evidence_file' class='form-control'>";
        echo "</td>";
        echo "</tr>";

        echo "<script>
        window.checkStatusOptante = function(val) {
           var rowStart = document.getElementById('row_date_start');
           var rowEnd = document.getElementById('row_date_end');
           var asterisk = document.getElementById('asterisk_obs');
           var rowEvType = document.getElementById('row_evidence_type');
           var rowEvLink = document.getElementById('row_evidence_link');
           var rowEvFile = document.getElementById('row_evidence_file');

           if (val == 4) { // Não optante
              if (rowStart) rowStart.style.display = 'none';
              if (rowEnd) rowEnd.style.display = 'none';
              if (asterisk) asterisk.style.display = 'inline';
              if (rowEvType) rowEvType.style.display = '';
              
              var evTypeElem = document.getElementsByName('evidence_type')[0];
              if (evTypeElem) {
                  checkEvidenceType(evTypeElem.value);
              }
           } else {
              if (rowStart) rowStart.style.display = '';
              if (rowEnd) rowEnd.style.display = '';
              if (asterisk) asterisk.style.display = 'none';
              if (rowEvType) rowEvType.style.display = 'none';
              if (rowEvLink) rowEvLink.style.display = 'none';
              if (rowEvFile) rowEvFile.style.display = 'none';
           }
        };

        window.checkEvidenceType = function(val) {
            var rowEvLink = document.getElementById('row_evidence_link');
            var rowEvFile = document.getElementById('row_evidence_file');
            var statusElem = document.getElementsByName('status')[0];
            var status = statusElem ? statusElem.value : 0;

            if (status == 4) {
                if (val == 1) { // Arquivo
                    if (rowEvLink) rowEvLink.style.display = 'none';
                    if (rowEvFile) rowEvFile.style.display = '';
                } else if (val == 2) { // Link
                    if (rowEvLink) rowEvLink.style.display = '';
                    if (rowEvFile) rowEvFile.style.display = 'none';
                } else {
                    if (rowEvLink) rowEvLink.style.display = 'none';
                    if (rowEvFile) rowEvFile.style.display = 'none';
                }
            }
        };

        // Inicialização
        setTimeout(function() {
            var statusElem = document.getElementsByName('status')[0];
            if (statusElem) {
                checkStatusOptante(statusElem.value);
            }
        }, 100);
        </script>";

        echo "<tr class='tab_bg_2'>";
        echo "<td colspan='2' class='center'>";
        echo "<input type='hidden' name='id' value='".$this->fields['id']."'>";
        echo "<input type='hidden' name='plugin_taskmaster_implementationtasks_id' value='".$this->fields['plugin_taskmaster_implementationtasks_id']."'>";
        echo "<input type='hidden' name='plugin_taskmaster_subtasks_id' value='".$this->fields['plugin_taskmaster_subtasks_id']."'>";
        echo "<input type='submit' name='update' value='" . _sx('button', 'Save') . "' class='btn btn-primary submit'>";
        echo "</td>";
        echo "</tr>";
        echo "</table>";
        echo "</div>";
        Html::closeForm();
        return true;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        if ($item->getType() == 'PluginTaskmasterImplementationSubtask') {
            return [
                1 => __('Histórico')
            ];
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
        if ($item->getType() == 'PluginTaskmasterImplementationSubtask') {
            switch ($tabnum) {
                case 1:
                    Log::showForItem($item);
                    break;
            }
        }
        return true;
    }

    public function prepareInputForAdd($input) {
        return $this->processEvidenceInput($input);
    }

    public function prepareInputForUpdate($input) {
        if (empty($input['users_id_analyst']) || $input['users_id_analyst'] <= 0) {
            Session::addMessageAfterRedirect("Analista Responsável é obrigatório.", false, ERROR);
            return false;
        }
        return $this->processEvidenceInput($input);
    }

    private function processEvidenceInput($input) {
        $status = isset($input['status']) ? $input['status'] : ($this->fields['status'] ?? 0);

        if ($status == 4) {
            $obs = isset($input['observacoes']) ? $input['observacoes'] : ($this->fields['observacoes'] ?? '');
            if (empty($obs)) {
                Session::addMessageAfterRedirect("Observações são obrigatórias para o status 'Não optante'.", false, ERROR);
                return false;
            }

            $evidence_type = isset($input['evidence_type']) ? $input['evidence_type'] : ($this->fields['evidence_type'] ?? 0);
            
            if (empty($evidence_type) || $evidence_type == 0) {
                Session::addMessageAfterRedirect("Tipo de evidência é obrigatório para o status 'Não optante'.", false, ERROR);
                return false;
            }

            if ($evidence_type == 2) { // Link
                if (isset($input['evidence_link'])) {
                    if (empty($input['evidence_link'])) {
                        Session::addMessageAfterRedirect("O link da evidência é obrigatório.", false, ERROR);
                        return false;
                    }
                    $input['evidence_data'] = $input['evidence_link'];
                }
            } else if ($evidence_type == 1) { // Arquivo
                $has_file = false;
                // Verifica se já existe um arquivo salvo E o tipo atual é arquivo
                if (!empty($this->fields['evidence_data']) && ($this->fields['evidence_type'] ?? 0) == 1) {
                    $has_file = true;
                }
                
                // Tenta detectar o arquivo em $_FILES (aceita qualquer nome que contenha 'evidence_file')
                $file_key = 'evidence_file';
                if (!isset($_FILES[$file_key]) || empty($_FILES[$file_key]['name'])) {
                    // Busca por qualquer chave que contenha evidence_file
                    foreach ($_FILES as $key => $file_data) {
                        if (strpos($key, 'evidence_file') !== false && !empty($file_data['name'])) {
                            $file_key = $key;
                            break;
                        }
                    }
                }

                if (isset($_FILES[$file_key]) && !empty($_FILES[$file_key]['name'])) {
                    if ($_FILES[$file_key]['error'] === UPLOAD_ERR_OK) {
                        $upload_dir = GLPI_PLUGIN_DOC_DIR . "/taskmaster/evidence/";
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }
                        
                        $filename = $_FILES[$file_key]['name'];
                        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                        
                        // ID for file name (use timestamp if new record)
                        $id = $this->fields['id'] ?? time();
                        $dest_name = "subtask_" . $id . "_" . time() . "." . $extension;
                        
                        if (move_uploaded_file($_FILES[$file_key]['tmp_name'], $upload_dir . $dest_name)) {
                            $input['evidence_data'] = $dest_name;
                            $has_file = true;
                        } else {
                            Session::addMessageAfterRedirect("Erro ao mover o arquivo para o diretório de destino. Verifique permissões.", false, ERROR);
                            return false;
                        }
                    } else {
                        $error_msg = "Erro no upload do arquivo (Código: " . $_FILES[$file_key]['error'] . ").";
                        if ($_FILES[$file_key]['error'] === UPLOAD_ERR_INI_SIZE) $error_msg .= " O arquivo excede o limite do servidor.";
                        Session::addMessageAfterRedirect($error_msg, false, ERROR);
                        return false;
                    }
                }
                
                if (!$has_file) {
                    Session::addMessageAfterRedirect("O arquivo de evidência é obrigatório.", false, ERROR);
                    return false;
                }
            }
        }
        return $input;
    }
}
