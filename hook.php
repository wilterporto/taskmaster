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
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
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
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `plugin_taskmaster_modules_id` INT UNSIGNED NOT NULL,
            `name` VARCHAR(255) DEFAULT NULL,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `date_creation` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `date_mod` TIMESTAMP NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `module` (`plugin_taskmaster_modules_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $DB->queryOrDie($query, "Error creating glpi_plugin_taskmaster_tasks");
    }

    if (!$DB->tableExists('glpi_plugin_taskmaster_subtasks')) {
        $query = "CREATE TABLE `glpi_plugin_taskmaster_subtasks` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `plugin_taskmaster_tasks_id` INT UNSIGNED NOT NULL,
            `name` VARCHAR(255) DEFAULT NULL,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `date_creation` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `date_mod` TIMESTAMP NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `task` (`plugin_taskmaster_tasks_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $DB->queryOrDie($query, "Error creating glpi_plugin_taskmaster_subtasks");
    }

    if (!$DB->tableExists('glpi_plugin_taskmaster_implementations')) {
        $query = "CREATE TABLE `glpi_plugin_taskmaster_implementations` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(255) DEFAULT NULL,
            `entities_id` INT UNSIGNED NOT NULL DEFAULT 0,
            `users_id_responsible` INT UNSIGNED NOT NULL DEFAULT 0,
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
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `plugin_taskmaster_implementations_id` INT UNSIGNED NOT NULL,
            `plugin_taskmaster_modules_id` INT UNSIGNED NOT NULL,
            PRIMARY KEY (`id`),
            KEY `implementation` (`plugin_taskmaster_implementations_id`),
            KEY `module` (`plugin_taskmaster_modules_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $DB->queryOrDie($query, "Error creating glpi_plugin_taskmaster_implementations_modules");
    }

    if (!$DB->tableExists('glpi_plugin_taskmaster_implementationtasks')) {
        $query = "CREATE TABLE `glpi_plugin_taskmaster_implementationtasks` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `plugin_taskmaster_implementations_id` INT UNSIGNED NOT NULL,
            `plugin_taskmaster_tasks_id` INT UNSIGNED NOT NULL,
            `status` INT(11) NOT NULL DEFAULT 0,
            `status_uso` TINYINT(1) NOT NULL DEFAULT 0,
            `users_id_analyst` INT UNSIGNED DEFAULT 0,
            `date_start` DATETIME NULL DEFAULT NULL,
            `date_end` DATETIME NULL DEFAULT NULL,
            `observacoes` LONGTEXT DEFAULT NULL,
            `evidence_type` INT(11) DEFAULT 0,
            `evidence_data` TEXT DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `implementation` (`plugin_taskmaster_implementations_id`),
            KEY `analyst` (`users_id_analyst`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $DB->queryOrDie($query, "Error creating glpi_plugin_taskmaster_implementationtasks");
    }

    if (!$DB->tableExists('glpi_plugin_taskmaster_implementationsubtasks')) {
        $query = "CREATE TABLE `glpi_plugin_taskmaster_implementationsubtasks` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `plugin_taskmaster_implementationtasks_id` INT UNSIGNED NOT NULL,
            `plugin_taskmaster_subtasks_id` INT UNSIGNED NOT NULL,
            `status` INT(11) NOT NULL DEFAULT 0,
            `status_uso` TINYINT(1) NOT NULL DEFAULT 0,
            `users_id_analyst` INT UNSIGNED DEFAULT 0,
            `date_start` DATETIME NULL DEFAULT NULL,
            `date_end` DATETIME NULL DEFAULT NULL,
            `observacoes` LONGTEXT DEFAULT NULL,
            `evidence_type` INT(11) DEFAULT 0,
            `evidence_data` TEXT DEFAULT NULL,
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
    global $DB;
    $migration = new Migration(TASKMASTER_VERSION);

    // Converte colunas para LONGTEXT se existirem
    if ($DB->tableExists('glpi_plugin_taskmaster_implementationtasks')) {
        $migration->displayMessage("Alterando observacoes para LONGTEXT em glpi_plugin_taskmaster_implementationtasks");
        $migration->addPostQuery("ALTER TABLE `glpi_plugin_taskmaster_implementationtasks` MODIFY `observacoes` LONGTEXT DEFAULT NULL");
    }

    if ($DB->tableExists('glpi_plugin_taskmaster_implementationsubtasks')) {
        $migration->displayMessage("Alterando observacoes para LONGTEXT em glpi_plugin_taskmaster_implementationsubtasks");
        $migration->addPostQuery("ALTER TABLE `glpi_plugin_taskmaster_implementationsubtasks` MODIFY `observacoes` LONGTEXT DEFAULT NULL");
    }

    // Adiciona campos de evidência
    if ($DB->tableExists('glpi_plugin_taskmaster_implementationtasks')) {
        if (!$DB->columnExists('glpi_plugin_taskmaster_implementationtasks', 'evidence_type')) {
            $DB->query("ALTER TABLE `glpi_plugin_taskmaster_implementationtasks` ADD `evidence_type` INT(11) DEFAULT 0");
        }
        if (!$DB->columnExists('glpi_plugin_taskmaster_implementationtasks', 'evidence_data')) {
            $DB->query("ALTER TABLE `glpi_plugin_taskmaster_implementationtasks` ADD `evidence_data` TEXT DEFAULT NULL");
        }
        if (!$DB->columnExists('glpi_plugin_taskmaster_implementationtasks', 'status_uso')) {
            $DB->query("ALTER TABLE `glpi_plugin_taskmaster_implementationtasks` ADD `status_uso` TINYINT(1) NOT NULL DEFAULT 0");
        }
    }

    if ($DB->tableExists('glpi_plugin_taskmaster_implementationsubtasks')) {
        if (!$DB->columnExists('glpi_plugin_taskmaster_implementationsubtasks', 'evidence_type')) {
            $DB->query("ALTER TABLE `glpi_plugin_taskmaster_implementationsubtasks` ADD `evidence_type` INT(11) DEFAULT 0");
        }
        if (!$DB->columnExists('glpi_plugin_taskmaster_implementationsubtasks', 'evidence_data')) {
            $DB->query("ALTER TABLE `glpi_plugin_taskmaster_implementationsubtasks` ADD `evidence_data` TEXT DEFAULT NULL");
        }
        if (!$DB->columnExists('glpi_plugin_taskmaster_implementationsubtasks', 'status_uso')) {
            $DB->query("ALTER TABLE `glpi_plugin_taskmaster_implementationsubtasks` ADD `status_uso` TINYINT(1) NOT NULL DEFAULT 0");
        }
    }

    if ($DB->tableExists('glpi_plugin_taskmaster_tasks')) {
        if (!$DB->columnExists('glpi_plugin_taskmaster_tasks', 'is_active')) {
            $DB->query("ALTER TABLE `glpi_plugin_taskmaster_tasks` ADD `is_active` TINYINT(1) NOT NULL DEFAULT 1");
        }
    }

    if ($DB->tableExists('glpi_plugin_taskmaster_subtasks')) {
        if (!$DB->columnExists('glpi_plugin_taskmaster_subtasks', 'is_active')) {
            $DB->query("ALTER TABLE `glpi_plugin_taskmaster_subtasks` ADD `is_active` TINYINT(1) NOT NULL DEFAULT 1");
        }
    }

    if ($DB->tableExists('glpi_plugin_taskmaster_modules')) {
        if (!$DB->columnExists('glpi_plugin_taskmaster_modules', 'training_hours')) {
            $DB->query("ALTER TABLE `glpi_plugin_taskmaster_modules` ADD `training_hours` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `name` ");
        }
    }

    if ($DB->tableExists('glpi_plugin_taskmaster_implementations')) {
        if (!$DB->columnExists('glpi_plugin_taskmaster_implementations', 'users_id_responsible')) {
            $DB->query("ALTER TABLE `glpi_plugin_taskmaster_implementations` ADD `users_id_responsible` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `entities_id` ");
            $DB->query("ALTER TABLE `glpi_plugin_taskmaster_implementations` ADD KEY `analyst` (`users_id_responsible`) ");
        }
        if (!$DB->columnExists('glpi_plugin_taskmaster_implementations', 'date_begin')) {
            $DB->query("ALTER TABLE `glpi_plugin_taskmaster_implementations` ADD `date_begin` DATETIME DEFAULT NULL AFTER `users_id_responsible` ");
        }
    }

    // Migração de chaves primárias e estrangeiras para UNSIGNED (padrão de boas práticas GLPI)
    $unsignedCols = [
        'glpi_plugin_taskmaster_modules' => [
            'id' => 'INT UNSIGNED NOT NULL AUTO_INCREMENT'
        ],
        'glpi_plugin_taskmaster_tasks' => [
            'id' => 'INT UNSIGNED NOT NULL AUTO_INCREMENT',
            'plugin_taskmaster_modules_id' => 'INT UNSIGNED NOT NULL'
        ],
        'glpi_plugin_taskmaster_subtasks' => [
            'id' => 'INT UNSIGNED NOT NULL AUTO_INCREMENT',
            'plugin_taskmaster_tasks_id' => 'INT UNSIGNED NOT NULL'
        ],
        'glpi_plugin_taskmaster_implementations' => [
            'id' => 'INT UNSIGNED NOT NULL AUTO_INCREMENT',
            'entities_id' => 'INT UNSIGNED NOT NULL DEFAULT 0',
            'users_id_responsible' => 'INT UNSIGNED NOT NULL DEFAULT 0'
        ],
        'glpi_plugin_taskmaster_implementations_modules' => [
            'id' => 'INT UNSIGNED NOT NULL AUTO_INCREMENT',
            'plugin_taskmaster_implementations_id' => 'INT UNSIGNED NOT NULL',
            'plugin_taskmaster_modules_id' => 'INT UNSIGNED NOT NULL'
        ],
        'glpi_plugin_taskmaster_implementationtasks' => [
            'id' => 'INT UNSIGNED NOT NULL AUTO_INCREMENT',
            'plugin_taskmaster_implementations_id' => 'INT UNSIGNED NOT NULL',
            'plugin_taskmaster_tasks_id' => 'INT UNSIGNED NOT NULL',
            'users_id_analyst' => 'INT UNSIGNED DEFAULT 0'
        ],
        'glpi_plugin_taskmaster_implementationsubtasks' => [
            'id' => 'INT UNSIGNED NOT NULL AUTO_INCREMENT',
            'plugin_taskmaster_implementationtasks_id' => 'INT UNSIGNED NOT NULL',
            'plugin_taskmaster_subtasks_id' => 'INT UNSIGNED NOT NULL',
            'users_id_analyst' => 'INT UNSIGNED DEFAULT 0'
        ]
    ];

    foreach ($unsignedCols as $table => $cols) {
        if ($DB->tableExists($table)) {
            foreach ($cols as $col => $definition) {
                if ($DB->columnExists($table, $col)) {
                    $DB->query("ALTER TABLE `$table` MODIFY `$col` $definition");
                }
            }
        }
    }

    $migration->executeMigration();
    return true;
}
