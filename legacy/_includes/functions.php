<?php
if (!file_exists('config.php')) {
    header(sprintf('Location: %s', str_contains($_SERVER['REQUEST_URI'], 'admin') ? 'installer.php' : 'admin/installer.php'), true, 302);
    exit();
}
if (isset($_GET["ysk"])) {
    session_id($_GET["ysk"]);
}
@session_start();
if (isset($_GET["CallSid"])) {
    insertSession($_GET["CallSid"]);
}
// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
