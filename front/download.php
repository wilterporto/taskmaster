<?php
include ("../../../inc/includes.php");

Session::checkLoginUser();

if (!isset($_GET['id']) || !isset($_GET['type'])) {
    Html::displayErrorAndDie("Parâmetros inválidos");
}

$id = $_GET['id'];
$type = $_GET['type'];

if ($type == 'task') {
    $item = new PluginTaskmasterImplementationTask();
} else if ($type == 'subtask') {
    $item = new PluginTaskmasterImplementationSubtask();
} else {
    Html::displayErrorAndDie("Tipo inválido");
}

if (!$item->getFromDB($id)) {
    Html::displayErrorAndDie("Item não encontrado");
}


if ($item->fields['evidence_type'] != 1 || empty($item->fields['evidence_data'])) {
    Html::displayErrorAndDie("Este item não possui um arquivo de evidência");
}

$evidence_dir = GLPI_PLUGIN_DOC_DIR . "/taskmaster/evidence/";
$file_path = $evidence_dir . $item->fields['evidence_data'];

if (!file_exists($file_path)) {
    $file_path = realpath($file_path);
}

if (!$file_path || !file_exists($file_path)) {
    Html::displayErrorAndDie("Arquivo não encontrado no servidor: " . $item->fields['evidence_data']);
}

// Limpa qualquer buffer
while (ob_get_level()) {
    ob_end_clean();
}

// Envio manual de cabeçalhos para garantir o download
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($file_path));

readfile($file_path);
exit();
