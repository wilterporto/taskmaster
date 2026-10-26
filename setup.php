<?php
define('TASKMASTER_VERSION', '11.0.0');
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
        'author'         => 'Wilter Porto',
        'license'        => 'GPLv2+',
        'homepage'       => '',
        'requirements'   => [
            'glpi' => [
                'min' => '11.0.0',
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
    if (version_compare(GLPI_VERSION, '11.0.0', 'lt') || version_compare(GLPI_VERSION, '12.0.0', 'ge')) {
        echo "Este plugin requer GLPI 11.0.0 ou superior (compatível até GLPI 11.x).";
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
