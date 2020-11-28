<?php require_once '_includes/functions.php';
$filePath = str_replace(basename($_SERVER['PHP_SELF']), "", $_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Meetings</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href='https://fonts.googleapis.com/css?family=Droid+Sans' rel='stylesheet' type='text/css'>
    <link rel="stylesheet" type="text/css" href="<?php echo sprintf("%spublic/dist/croutonjs/yap-meeting-results.min.css", $filePath) ?>" />
    <style type="text/css">
        #bmlt-map { display: none; }
    </style>
    <script src="<?php echo sprintf("%spublic/dist/croutonjs/yap-meeting-results.min.js", $filePath) ?>"></script>
    <script type="text/javascript">
        var crouton = new Crouton({
            root_server: "<?php echo getBMLTRootServer(); ?>",
            theme: "sezf",
            template_path: "<?php echo sprintf("%spublic/dist/croutonjs/templates", $filePath) ?>",
            has_languages: "1",
            time_format: "H:mm (h:mma) z",
            filter_tabs: 0,
            map_search: {
                latitude: <?php echo $_REQUEST["latitude"] ?>,
                longitude: <?php echo $_REQUEST["longitude"] ?>,
                coordinates_search: true,
                width: -100
            }
        });

        crouton.render();
    </script>
</head>
<body>
<div id="bmlt-tabs"></div>
</body>
</html>
