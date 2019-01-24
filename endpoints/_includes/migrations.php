<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/Database.php';
static $root_server_data_migration_flag = "root_server_data_migration";

if (isset($GLOBALS['mysql_hostname'])) {
    try {
        $db = new Database();
        try {
            $db->query("SELECT version FROM migrations ORDER BY version DESC LIMIT 1");
            $migration_version_query = intval($db->single()["version"]);
        } catch (PDOException $e) {
            $migration_version_query = null;
        }

        if (!isset($migration_version_query)) {
            $commands = file_get_contents("../migrations/0.sql");
            $db->exec($commands);
            $next_version = 1;
        } else {
            $next_version = intval($db->single()["version"]) + 1;
        }

        while (file_exists("../migrations/$next_version.sql")) {
            $commands = file_get_contents("../migrations/$next_version.sql");
            $db->exec($commands);
            $next_version_str = strval($next_version);
            $db->query("INSERT INTO migrations (version) VALUES ('$next_version_str')");
            $db->execute();
            $next_version++;
        }
    } catch (PDOException $e) {
        throw $e;
    } finally {
        $db->close();
    }
}
