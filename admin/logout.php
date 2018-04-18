<?php
include_once '../config.php';
include_once '../functions.php';

logout_auth();
header('Location: index.php');