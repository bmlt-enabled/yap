<?php
    include 'config.php';
    include 'functions.php';
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

    $exploded_result = explode("\|", $GLOBALS["helpline_fallback"]);
    $phone_number = isset($exploded_result[0]) ? $exploded_result[0] : "";
    $extension = isset($exploded_result[1]) ? $exploded_result[1] : "w";
?>
<Response>
    <Say voice="<?php echo $voice; ?>" language="<?php echo $language; ?>">There seems to be a problem... connecting you to someone now... please stand by.</Say>
    <Dial>
        <Number sendDigits="<?php echo $extension?>"><?php echo $phone_number ?></Number>
    </Dial>
</Response>