<?php
try {
    require $includePath;
} catch (\Twilio\Exceptions\ConfigurationException) {
    header("Content-Type: text/html");
    http_response_code(403);
    echo "Forbidden";
    exit;
}
