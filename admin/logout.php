<?php
require_once __DIR__.'/../endpoints/functions.php';

logout_auth($_SESSION['username']);
header('Location: index.php');
