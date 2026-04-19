<?php

/**
 * Install hook
 *
 * @return boolean
 */
function plugin_taskmaster_install() {
    global $DB;

    $migration = new Migration(TASKMASTER_VERSION);

    // Create tables
    if (!$DB->tableExists('glpi_plugin_taskmaster_modules')) {
        $query = "CREATE TABLE `glpi_plugin_taskmaster_modules` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(255) DEFAULT NULL,
            `training_hours` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `date_creation` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `date_mod` TIMESTAMP NULL DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $DB->queryOrDie($query, "Error creating glpi_plugin_taskmaster_modules");
    }

    if (!$DB->tableExists('glpi_plugin_taskmaster_tasks')) {
        $query = "CREATE TABLE `glpi_plugin_taskmaster_tasks` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `plugin_taskmaster_modules_id` INT(11) NOT NULL,
            `name` VARCHAR(255) DEFAULT NULL,
            `date_creation` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `date_mod` TIMESTAMP NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `module` (`plugin_taskmaster_modules_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $DB->queryOrDie($query, "Error creating glpi_plugin_taskmaster_tasks");
    }

    if (!$DB->tableExists('glpi_plugin_taskmaster_subtasks')) {
        $query = "CREATE TABLE `glpi_plugin_taskmaster_subtasks` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `plugin_taskmaster_tasks_id` INT(11) NOT NULL,
            `name` VARCHAR(255) DEFAULT NULL,
            `date_creation` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `date_mod` TIMESTAMP NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `task` (`plugin_taskmaster_tasks_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $DB->queryOrDie($query, "Error creating glpi_plugin_taskmaster_subtasks");
    }

    if (!$DB->tableExists('glpi_plugin_taskmaster_implementations')) {
        $query = "CREATE TABLE `glpi_plugin_taskmaster_implementations` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(255) DEFAULT NULL,
            `entities_id` INT(11) NOT NULL DEFAULT 0,
            `users_id_responsible` INT(11) NOT NULL DEFAULT 0,
            `date_begin` DATETIME DEFAULT NULL,
            `is_recursive` TINYINT(1) NOT NULL DEFAULT 0,
            `date_creation` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `date_mod` TIMESTAMP NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `entities_id` (`entities_id`),
            KEY `analyst` (`users_id_responsible`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $DB->queryOrDie($query, "Error creating glpi_plugin_taskmaster_implementations");
    }

    if (!$DB->tableExists('glpi_plugin_taskmaster_implementations_modules')) {
        $query = "CREATE TABLE `glpi_plugin_taskmaster_implementations_modules` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `plugin_taskmaster_implementations_id` INT(11) NOT NULL,
            `plugin_taskmaster_modules_id` INT(11) NOT NULL,
            PRIMARY KEY (`id`),
            KEY `implementation` (`plugin_taskmaster_implementations_id`),
            KEY `module` (`plugin_taskmaster_modules_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $DB->queryOrDie($query, "Error creating glpi_plugin_taskmaster_implementations_modules");
    }

    if (!$DB->tableExists('glpi_plugin_taskmaster_implementationtasks')) {
        $query = "CREATE TABLE `glpi_plugin_taskmaster_implementationtasks` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `plugin_taskmaster_implementations_id` INT(11) NOT NULL,
            `plugin_taskmaster_tasks_id` INT(11) NOT NULL,
            `status` INT(11) NOT NULL DEFAULT 0,
            `users_id_analyst` INT(11) DEFAULT 0,
            `date_start` DATETIME NULL DEFAULT NULL,
            `date_end` DATETIME NULL DEFAULT NULL,
            `observacoes` TEXT DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `implementation` (`plugin_taskmaster_implementations_id`),
            KEY `analyst` (`users_id_analyst`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $DB->queryOrDie($query, "Error creating glpi_plugin_taskmaster_implementationtasks");
    }

    if (!$DB->tableExists('glpi_plugin_taskmaster_implementationsubtasks')) {
        $query = "CREATE TABLE `glpi_plugin_taskmaster_implementationsubtasks` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `plugin_taskmaster_implementationtasks_id` INT(11) NOT NULL,
            `plugin_taskmaster_subtasks_id` INT(11) NOT NULL,
            `status` INT(11) NOT NULL DEFAULT 0,
            `users_id_analyst` INT(11) DEFAULT 0,
            `date_start` DATETIME NULL DEFAULT NULL,
            `date_end` DATETIME NULL DEFAULT NULL,
            `observacoes` TEXT DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `implemtask` (`plugin_taskmaster_implementationtasks_id`),
            KEY `analyst` (`users_id_analyst`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $DB->queryOrDie($query, "Error creating glpi_plugin_taskmaster_implementationsubtasks");
    }

    $migration->executeMigration();

    return true;
}

/**
 * Uninstall hook
 *
 * @return boolean
 */
function plugin_taskmaster_uninstall() {
    global $DB;

    $tables = [
        'glpi_plugin_taskmaster_modules',
        'glpi_plugin_taskmaster_tasks',
        'glpi_plugin_taskmaster_subtasks',
        'glpi_plugin_taskmaster_implementations',
        'glpi_plugin_taskmaster_implementations_modules',
        'glpi_plugin_taskmaster_implementationtasks',
        'glpi_plugin_taskmaster_implementationsubtasks'
    ];

    foreach ($tables as $table) {
        $DB->dropTable($table);
    }

    $req = $DB->request('glpi_profilerights', [
        'name' => ['plugin_taskmaster_module', 'plugin_taskmaster_implementation']
    ]);
    foreach ($req as $row) {
        $DB->delete('glpi_profilerights', ['id' => $row['id']]);
    }

    return true;
}

/**
 * Upgrade hook
 *
 * @return boolean
 */
function plugin_taskmaster_upgrade() {
    return true;
}
