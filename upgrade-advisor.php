<?php
    include_once 'functions.php';
    header( "Content-Type: application/json" );
    echo json_encode(UpgradeAdvisor::getStatus());
