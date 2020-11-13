<?php
require_once __DIR__ . '/../endpoints/_includes/functions.php';

logout_auth($_SESSION['username']);
header('Location: index.php');
