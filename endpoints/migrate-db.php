<?php
require_once 'db.php';

$migration_version_query = $conn->query("SELECT 1 FROM migrations");

if (!$migration_version_query) {
    $commands = file_get_contents("../migrations/0.sql");
    $conn->exec($commands);
}

