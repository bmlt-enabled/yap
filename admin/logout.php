<?php
include_once '../config.php';
include_once '../functions.php';

logout_auth($_SESSION['username']);
header('Location: index.php');
