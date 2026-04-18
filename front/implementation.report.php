<?php

include("../../../inc/includes.php");

Session::checkRight("plugin_taskmaster_impl", READ);

$impl_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($impl_id <= 0) {
    die("ID de implantação inválido.");
}

$impl = new PluginTaskmasterImplementation();
if (!$impl->getFromDB($impl_id)) {
    die("Implantação não encontrada.");
}

global $DB;

// ── Nome da entidade ──────────────────────────────────────────────────────────
$ent_full_name = Dropdown::getDropdownName('glpi_entities', $impl->fields['entities_id']);
$decoded_name  = html_entity_decode($ent_full_name, ENT_QUOTES, 'UTF-8');
$ent_parts     = preg_split('/\s*>\s*/', $decoded_name);
if (count($ent_parts) > 1) {
    array_shift($ent_parts);
    $entity_name = implode(' > ', $ent_parts);
} else {
    $entity_name = $decoded_name;
}

// ── Localização da entidade raiz (id = 0) ────────────────────────────────────
$rootEntityRow = $DB->request(['FROM' => 'glpi_entities', 'WHERE' => ['id' => 0]])->current();
$rootCity  = '';
$rootState = '';
if ($rootEntityRow) {
    $rootEntityName = trim($rootEntityRow['name'] ?? '');
    $rootCity  = trim($rootEntityRow['town']  ?? '');
    $rootState = trim($rootEntityRow['state'] ?? '');
}
$footerLocation = '';
if ($rootCity !== '' && $rootState !== '') {
    $footerLocation = $rootCity . '-' . $rootState . ', ';
} elseif ($rootCity !== '') {
    $footerLocation = $rootCity . ', ';
} elseif ($rootState !== '') {
    $footerLocation = $rootState . ', ';
}

// ── Módulos vinculados ────────────────────────────────────────────────────────
$reqMods = $DB->request('glpi_plugin_taskmaster_implementations_modules', [
    'plugin_taskmaster_implementations_id' => $impl_id
]);
$moduleIds = [];
foreach ($reqMods as $rm) {
    $moduleIds[] = $rm['plugin_taskmaster_modules_id'];
}

// ── Tarefas e subtarefas da implantação ───────────────────────────────────────
$tasksReq = $DB->request([
    'FROM'  => 'glpi_plugin_taskmaster_implementationtasks',
    'WHERE' => ['plugin_taskmaster_implementations_id' => $impl_id]
]);

$globalTotal = 0;
$globalDone  = 0;
$tasksByModule = [];    // [module_id => ['total' => int, 'done' => int]]

foreach ($tasksReq as $t) {
    $tObj = new PluginTaskmasterTask();
    if (!$tObj->getFromDB($t['plugin_taskmaster_tasks_id'])) continue;
    $modId = $tObj->fields['plugin_taskmaster_modules_id'];

    if (!isset($tasksByModule[$modId])) {
        $tasksByModule[$modId] = ['total' => 0, 'done' => 0];
    }

    $globalTotal++;
    $tasksByModule[$modId]['total']++;
    if ($t['status'] == 3 || $t['status'] == 4) {
        $globalDone++;
        $tasksByModule[$modId]['done']++;
    }

    // Subtarefas
    $subReq = $DB->request([
        'FROM'  => 'glpi_plugin_taskmaster_implementationsubtasks',
        'WHERE' => ['plugin_taskmaster_implementationtasks_id' => $t['id']]
    ]);
    foreach ($subReq as $s) {
        $globalTotal++;
        $tasksByModule[$modId]['total']++;
        if ($s['status'] == 3 || $s['status'] == 4) {
            $globalDone++;
            $tasksByModule[$modId]['done']++;
        }
    }
}

$globalProgress = $globalTotal > 0 ? round(($globalDone / $globalTotal) * 100, 1) : 0;

// ── Módulos em ordem alfabética ───────────────────────────────────────────────
$modulesData = [];
foreach ($moduleIds as $mId) {
    $mod = new PluginTaskmasterModule();
    if (!$mod->getFromDB($mId)) continue;
    $stats = $tasksByModule[$mId] ?? ['total' => 0, 'done' => 0];
    $pct   = $stats['total'] > 0 ? round(($stats['done'] / $stats['total']) * 100, 1) : 0;
    $modulesData[] = [
        'name'     => $mod->fields['name'],
        'total'    => $stats['total'],
        'done'     => $stats['done'],
        'progress' => $pct,
    ];
}
usort($modulesData, fn($a, $b) => strcmp($a['name'], $b['name']));

// ── Data de geração ───────────────────────────────────────────────────────────
$generatedAt = date('d/m/Y \à\s H:i');

// ── Cor por faixa de progresso ────────────────────────────────────────────────
function progressColor(float $pct): string {
    if ($pct >= 100) return '#2e7d32';   // verde escuro
    if ($pct >= 50)  return '#1565c0';   // azul escuro
    if ($pct > 0)    return '#e65100';   // laranja escuro
    return '#b71c1c';                    // vermelho escuro
}

function progressBgColor(float $pct): string {
    if ($pct >= 100) return '#e8f5e9';
    if ($pct >= 50)  return '#e3f2fd';
    if ($pct > 0)    return '#fff3e0';
    return '#ffebee';
}

function progressBarColor(float $pct): string {
    if ($pct >= 100) return '#4caf50';
    if ($pct >= 50)  return '#2196f3';
    if ($pct > 0)    return '#ff9800';
    return '#f44336';
}

?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Implantação — <?= htmlspecialchars($impl->fields['name']) ?></title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', Arial, sans-serif;
            background: #f4f6f9;
            color: #1a2035;
            font-size: 13px;
            line-height: 1.5;
        }

        /* ── Wrapper ── */
        .report-wrapper {
            max-width: 820px;
            margin: 32px auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 24px rgba(0,0,0,.10);
            overflow: hidden;
        }

        /* ── Cabeçalho ── */
        .report-header {
            background: linear-gradient(135deg, #1a237e 0%, #283593 60%, #3949ab 100%);
            color: #fff;
            padding: 28px 36px 24px;
        }
        .report-header .label {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            opacity: .75;
            margin-bottom: 6px;
        }
        .report-header h1 {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .report-header .entity {
            font-size: 13px;
            opacity: .85;
        }
        .report-header .meta-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 18px;
            opacity: .80;
            font-size: 11px;
        }

        /* ── Progresso Global ── */
        .global-progress-section {
            padding: 24px 36px;
            border-bottom: 1px solid #e8eaf0;
            background: #f8f9fc;
        }
        .global-progress-section h2 {
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #5c6bc0;
            margin-bottom: 14px;
        }
        .global-card {
            display: flex;
            align-items: center;
            gap: 20px;
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 16px 20px;
        }
        .global-pct-circle {
            flex-shrink: 0;
            width: 72px;
            height: 72px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: 700;
            color: #fff;
        }
        .global-info {
            flex: 1;
        }
        .global-info .title {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 6px;
        }
        .bar-track {
            width: 100%;
            height: 10px;
            background: #e9ecef;
            border-radius: 999px;
            overflow: hidden;
        }
        .bar-fill {
            height: 100%;
            border-radius: 999px;
            transition: width .4s;
        }
        .global-info .counter {
            margin-top: 5px;
            font-size: 11px;
            color: #78909c;
        }

        /* ── Tabela de Módulos ── */
        .modules-section {
            padding: 24px 36px 32px;
        }
        .modules-section h2 {
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #5c6bc0;
            margin-bottom: 14px;
        }
        table.modules-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12.5px;
        }
        table.modules-table thead tr {
            background: #e8eaf6;
            color: #3949ab;
        }
        table.modules-table thead th {
            padding: 9px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 11px;
            letter-spacing: .5px;
            text-transform: uppercase;
        }
        table.modules-table thead th.center { text-align: center; }
        table.modules-table thead th.right  { text-align: right; }

        table.modules-table tbody tr {
            border-bottom: 1px solid #f0f0f0;
        }
        table.modules-table tbody tr:last-child { border-bottom: none; }
        table.modules-table tbody tr:hover { background: #f8f9fc; }

        table.modules-table td {
            padding: 10px 12px;
            vertical-align: middle;
        }
        table.modules-table td.center { text-align: center; }
        table.modules-table td.right  { text-align: right; }

        .mod-name { font-weight: 500; }
        .pct-badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 999px;
            font-weight: 700;
            font-size: 12px;
        }
        .mini-bar-track {
            width: 100%;
            min-width: 100px;
            height: 8px;
            background: #e9ecef;
            border-radius: 999px;
            overflow: hidden;
        }
        .mini-bar-fill {
            height: 100%;
            border-radius: 999px;
        }

        /* ── Rodapé ── */
        .report-footer {
            padding: 14px 36px;
            border-top: 1px solid #e8eaf0;
            background: #f8f9fc;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11px;
            color: #90a4ae;
        }

        /* ── Botões de ação (não aparecem na impressão) ── */
        .action-bar {
            max-width: 820px;
            margin: 0 auto 16px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding: 0 4px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 18px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-family: inherit;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: opacity .15s;
        }
        .btn:hover { opacity: .85; }
        .btn-print  { background: #1a237e; color: #fff; }
        .btn-back   { background: #e0e0e0; color: #37474f; }

        /* ── Print ── */
        @page {
            size: A4 portrait;
            margin: 0;          /* Remove a área de margem onde o browser insere URL e nº de página */
        }
        @media print {
            body {
                background: #fff;
                font-size: 12px;
                padding: 12mm 10mm; /* Compensa a margem zerada */
            }
            .action-bar  { display: none !important; }
            .report-wrapper {
                max-width: 100%;
                margin: 0;
                box-shadow: none;
                border-radius: 0;
            }
            .report-header { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .global-pct-circle { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .pct-badge { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .bar-fill, .mini-bar-fill { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            table.modules-table thead tr { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>

<!-- Barra de ações -->
<div class="action-bar">
    <a class="btn btn-back" href="javascript:history.back()">&#8592; Voltar</a>
    <button class="btn btn-print" onclick="window.print()">🖨️ Imprimir / Salvar PDF</button>
</div>

<div class="report-wrapper">

    <!-- Cabeçalho -->
    <div class="report-header">
        <div class="label"><?= !empty($rootEntityName) ? htmlspecialchars($rootEntityName) . ' — ' : '' ?>Relatório de Implantação</div>
        <h1><?= htmlspecialchars($impl->fields['name']) ?></h1>
        <div class="entity">📍 <?= htmlspecialchars($entity_name) ?></div>
        <div class="meta-row">
            <span>Gerado em: <?= $generatedAt ?></span>
        </div>
    </div>

    <!-- Progresso Global -->
    <div class="global-progress-section">
        <h2>Progresso Geral</h2>
        <div class="global-card">
            <div class="global-pct-circle" style="background:<?= progressBarColor($globalProgress) ?>;">
                <?= $globalProgress ?>%
            </div>
            <div class="global-info">
                <div class="title">Conclusão total da implantação</div>
                <div class="bar-track">
                    <div class="bar-fill" style="width:<?= $globalProgress ?>%; background:<?= progressBarColor($globalProgress) ?>;"></div>
                </div>
                <div class="counter"><?= $globalDone ?> de <?= $globalTotal ?> item<?= $globalTotal != 1 ? 's' : '' ?> concluído<?= $globalDone != 1 ? 's' : '' ?> (tarefas + subtarefas)</div>
            </div>
        </div>
    </div>

    <!-- Tabela de Módulos -->
    <div class="modules-section">
        <h2>Progresso por Módulo (ordem alfabética)</h2>

        <?php if (empty($modulesData)): ?>
            <p style="color:#90a4ae; font-style:italic;">Nenhum módulo vinculado a esta implantação.</p>
        <?php else: ?>
        <table class="modules-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Módulo</th>
                    <th class="center">Itens</th>
                    <th style="width:180px;">Progresso</th>
                    <th class="right" style="width:70px;">%</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($modulesData as $i => $mod): ?>
                <?php
                    $bgColor  = progressBgColor($mod['progress']);
                    $txtColor = progressColor($mod['progress']);
                    $barColor = progressBarColor($mod['progress']);
                ?>
                <tr>
                    <td style="color:#90a4ae; width:30px;"><?= $i + 1 ?></td>
                    <td class="mod-name"><?= htmlspecialchars($mod['name']) ?></td>
                    <td class="center" style="color:#546e7a; font-size:11px;">
                        <?= $mod['done'] ?> / <?= $mod['total'] ?>
                    </td>
                    <td>
                        <div class="mini-bar-track">
                            <div class="mini-bar-fill" style="width:<?= $mod['progress'] ?>%; background:<?= $barColor ?>;"></div>
                        </div>
                    </td>
                    <td class="right">
                        <span class="pct-badge" style="background:<?= $bgColor ?>; color:<?= $txtColor ?>;">
                            <?= $mod['progress'] ?>%
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- Rodapé -->
    <div class="report-footer">
        <span><?= htmlspecialchars($footerLocation . $generatedAt) ?></span>
    </div>

</div>

</body>
</html>
