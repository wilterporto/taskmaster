<?php
define('TASKMASTER_VERSION', '1.2.0');
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
    Plugin::registerClass('PluginTaskmasterProfile', ['addtabon' => ['Profile']]);
    Plugin::registerClass('PluginTaskmasterModule', ['addtabon' => ['PluginTaskmasterModule']]);
    Plugin::registerClass('PluginTaskmasterTask', ['addtabon' => ['PluginTaskmasterModule', 'PluginTaskmasterTask']]);
    Plugin::registerClass('PluginTaskmasterSubtask', ['addtabon' => ['PluginTaskmasterTask']]);
    Plugin::registerClass('PluginTaskmasterImplementation', ['addtabon' => ['PluginTaskmasterImplementation']]);
    Plugin::registerClass('PluginTaskmasterImplementationTask');
    Plugin::registerClass('PluginTaskmasterImplementationSubtask');

    // Menus
    $menuItems = [];
    if (Session::haveRight('plugin_taskmaster_implementation', READ)) {
        $menuItems[] = 'PluginTaskmasterImplementation';
    }
    if (Session::haveRight('plugin_taskmaster_module', READ)) {
        $menuItems[] = 'PluginTaskmasterModule';
    }

    if (!empty($menuItems)) {
        $PLUGIN_HOOKS['menu_toadd']['taskmaster'] = ['tools' => $menuItems];
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
