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

    if (!$DB->tableExists('glpi_plugin_taskmaster_configs')) {
        $query = "CREATE TABLE `glpi_plugin_taskmaster_configs` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(100) NOT NULL,
            `value` TEXT,
            PRIMARY KEY (`id`),
            UNIQUE KEY `name` (`name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $DB->queryOrDie($query, "Error creating glpi_plugin_taskmaster_configs");

        // Insert initial configs
        $DB->insert('glpi_plugin_taskmaster_configs', [
            'name' => 'analyst_profiles',
            'value' => '[]'
        ]);
        $DB->insert('glpi_plugin_taskmaster_configs', [
            'name' => 'manager_profiles',
            'value' => '[]'
        ]);
    }

    // Rights migration - garante que os direitos existam na tabela
    $allRights = CREATE | READ | UPDATE | PURGE;

    // Busca todos os perfis com interface 'central' (administradores)
    $profiles = $DB->request('glpi_profiles', ['interface' => 'central']);
    foreach ($profiles as $profile) {
        $pid = $profile['id'];

        foreach (['plugin_taskmaster_manage', 'plugin_taskmaster_impl'] as $rightName) {
            // Verifica se já existe o direito para este perfil
            $existing = $DB->request('glpi_profilerights', [
                'profiles_id' => $pid,
                'name'        => $rightName
            ]);
            if (count($existing) == 0) {
                $DB->insert('glpi_profilerights', [
                    'profiles_id' => $pid,
                    'name'        => $rightName,
                    'rights'      => $allRights
                ]);
            } else {
                // Atualiza para garantir permissões completas
                $DB->update('glpi_profilerights', [
                    'rights' => $allRights
                ], [
                    'profiles_id' => $pid,
                    'name'        => $rightName
                ]);
            }
        }
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
        'glpi_plugin_taskmaster_implementationsubtasks',
        'glpi_plugin_taskmaster_configs'
    ];

    foreach ($tables as $table) {
        $DB->dropTable($table);
    }

    $req = $DB->request('glpi_profilerights', [
        'name' => ['plugin_taskmaster_manage', 'plugin_taskmaster_impl']
    ]);
    foreach ($req as $row) {
        $DB->delete('glpi_profilerights', ['id' => $row['id']]);
    }

    return true;
}
