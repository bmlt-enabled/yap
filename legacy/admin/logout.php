<?php
require_once __DIR__ . '/../_includes/functions.php';

logout_auth($_SESSION['username']);
header('Location: index.php');
