<?php
require_once '../config.php';
$conn = new PDO(sprintf("mysql:host=%s;dbname=%s", $GLOBALS['mysql_hostname'], $GLOBALS['mysql_database']), $GLOBALS['mysql_username'], $GLOBALS['mysql_password']);
