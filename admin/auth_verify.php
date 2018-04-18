<?php
include_once '../config.php';
include_once '../functions.php';

if (!check_auth()) {
    header('Location: index.php?auth=false');
}
