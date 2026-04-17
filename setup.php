<?php
define('TASKMASTER_VERSION', '1.1.0');
define('TASKMASTER_DIR', dirname(__FILE__));

/**
 * Init the hooks of the plugins
 *
 * @return void
 */
function plugin_init_taskmaster() {
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant']['taskmaster'] = true;

    // Configuração de classes
    Plugin::registerClass('PluginTaskmasterConfig', ['addtabon' => ['Config']]);
    Plugin::registerClass('PluginTaskmasterProfile', ['addtabon' => ['Profile']]);
    Plugin::registerClass('PluginTaskmasterModule', ['addtabon' => ['PluginTaskmasterModule']]);
    Plugin::registerClass('PluginTaskmasterTask', ['addtabon' => ['PluginTaskmasterModule', 'PluginTaskmasterTask']]);
    Plugin::registerClass('PluginTaskmasterSubtask', ['addtabon' => ['PluginTaskmasterTask']]);
    Plugin::registerClass('PluginTaskmasterImplementation', ['addtabon' => ['PluginTaskmasterImplementation']]);
    Plugin::registerClass('PluginTaskmasterImplementationTask');
    Plugin::registerClass('PluginTaskmasterImplementationSubtask');

    // Menus — Implantações: visível para quem tem acesso impl
    $menuItems = [];
    if (Session::haveRight('plugin_taskmaster_impl', READ)) {
        $menuItems[] = 'PluginTaskmasterImplementation';
    }
    // Módulos e Config: somente para quem tem direito de gerenciar (admin)
    if (Session::haveRight('plugin_taskmaster_manage', READ)) {
        $menuItems[] = 'PluginTaskmasterModule';
        $menuItems[] = 'PluginTaskmasterConfig';
    }
    if (!empty($menuItems)) {
        $PLUGIN_HOOKS['menu_toadd']['taskmaster'] = ['tools' => $menuItems];
    }

    // Config page — somente admin
    if (Session::haveRight('plugin_taskmaster_manage', READ)) {
        $PLUGIN_HOOKS['config_page']['taskmaster'] = 'front/config.form.php';
    }

    // Auto-update schema for new mandatory fields
    global $DB;
    if ($DB->tableExists('glpi_plugin_taskmaster_implementations')) {
        if (!$DB->fieldExists('glpi_plugin_taskmaster_implementations', 'users_id_responsible')) {
            $DB->query("ALTER TABLE `glpi_plugin_taskmaster_implementations` ADD `users_id_responsible` INT(11) NOT NULL DEFAULT 0 AFTER `entities_id` ");
            $DB->query("ALTER TABLE `glpi_plugin_taskmaster_implementations` ADD KEY `analyst` (`users_id_responsible`) ");
        }
        if (!$DB->fieldExists('glpi_plugin_taskmaster_implementations', 'date_begin')) {
            $DB->query("ALTER TABLE `glpi_plugin_taskmaster_implementations` ADD `date_begin` DATETIME DEFAULT NULL AFTER `users_id_responsible` ");
        }
    }

    if ($DB->tableExists('glpi_plugin_taskmaster_implementationtasks')) {
        if (!$DB->fieldExists('glpi_plugin_taskmaster_implementationtasks', 'observacoes')) {
            $DB->query("ALTER TABLE `glpi_plugin_taskmaster_implementationtasks` ADD `observacoes` TEXT DEFAULT NULL ");
        }
    }

    if ($DB->tableExists('glpi_plugin_taskmaster_implementationsubtasks')) {
        if (!$DB->fieldExists('glpi_plugin_taskmaster_implementationsubtasks', 'observacoes')) {
            $DB->query("ALTER TABLE `glpi_plugin_taskmaster_implementationsubtasks` ADD `observacoes` TEXT DEFAULT NULL ");
        }
    }

    if ($DB->tableExists('glpi_plugin_taskmaster_tasks')) {
        if (!$DB->fieldExists('glpi_plugin_taskmaster_tasks', 'is_active')) {
            $DB->query("ALTER TABLE `glpi_plugin_taskmaster_tasks` ADD `is_active` TINYINT(1) NOT NULL DEFAULT 1 ");
        }
    }

    if ($DB->tableExists('glpi_plugin_taskmaster_subtasks')) {
        if (!$DB->fieldExists('glpi_plugin_taskmaster_subtasks', 'is_active')) {
            $DB->query("ALTER TABLE `glpi_plugin_taskmaster_subtasks` ADD `is_active` TINYINT(1) NOT NULL DEFAULT 1 ");
        }
    }

    // Auto-correção de permissões: garante que perfis 'central' tenham direitos
    if (Session::getLoginUserID()) {
        $allRights = CREATE | READ | UPDATE | PURGE;
        $profileId = $_SESSION['glpiactiveprofile']['id'] ?? 0;
        if ($profileId > 0) {
            foreach (['plugin_taskmaster_manage', 'plugin_taskmaster_impl'] as $rightName) {
                $existing = $DB->request('glpi_profilerights', [
                    'profiles_id' => $profileId,
                    'name'        => $rightName
                ]);
                if (count($existing) == 0) {
                    $DB->insert('glpi_profilerights', [
                        'profiles_id' => $profileId,
                        'name'        => $rightName,
                        'rights'      => $allRights
                    ]);
                    // Atualiza sessão para refletir imediatamente
                    $_SESSION['glpiactiveprofile'][$rightName] = $allRights;
                }
            }
        }
    }
}

/**
 * Get the name and the version of the plugin
 *
 * @return array
 */
function plugin_version_taskmaster() {
    return [
        'name'           => 'Taskmaster',
        'version'        => TASKMASTER_VERSION,
        'author'         => 'Wilter P. Porto',
        'license'        => 'GPLv2+',
        'homepage'       => '',
        'requirements'   => [
            'glpi' => [
                'min' => '10.0.0',
                'max' => '12.0.0'
            ]
        ]
    ];
}

/**
 * Check pre-requisites before install
 *
 * @return boolean
 */
function plugin_taskmaster_check_prerequisites() {
    if (version_compare(GLPI_VERSION, '10.0.0', 'lt')) {
        echo "Este plugin requer GLPI 10.0.0 ou superior.";
        return false;
    }
    return true;
}

/**
 * Check configuration process
 *
 * @return boolean
 */
function plugin_taskmaster_check_config() {
    return true;
}
