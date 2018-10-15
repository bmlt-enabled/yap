<?php
if (intval(preg_match('/.*(?:api|admin|lang).*/', $_SERVER["REQUEST_URI"])) == 0) {
    $path_array = explode("/", $_SERVER["REQUEST_URI"]);
    $filename = $path_array[count($path_array) - 1];
    $new_file_path = "endpoints/" . ($filename !== "" ? $filename : "index.php");
    $query_pos = strpos($new_file_path, "?");
    $mod_file_path = $query_pos ? substr($new_file_path, 0, $query_pos) : $new_file_path;
    try {
        if (!@require_once($mod_file_path)) {
            throw new Exception();
        }
    } catch (Exception $e) {
        http_response_code(404);
        echo "404";
    }
} else {
    return false;
}
