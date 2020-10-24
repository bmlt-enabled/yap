<?php
require_once __DIR__ . '/../endpoints/_includes/functions.php';
if (isset($_SESSION['auth_is_admin']) && $_SESSION['auth_is_admin'] == 1) {
    clearCache();
    echo "Cache cleared";
} else {
    http_response_code(404);
}
