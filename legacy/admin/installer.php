<?php
require_once __DIR__ . '/../endpoints/_includes/constants.php';
if (file_exists('../config.php')) {
    define('URL', str_replace('installer.php', '', $_SERVER['REQUEST_URI']));
    header("Location: " . URL);
    exit();
}
require_once 'spinner_dialog.php';
?>
<!doctype html>
<html lang="en">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="dist/css/yap.min.css?v=<?php echo time()?>">
    <title>Yap Admin</title>
</head>
<body>
    <div id="wizard" class="container">
        <form id="wizardForm" class="wizardForm">
            <div id="yap-logo"></div>
            <div id="wizardTitle">Installer</div>
            <div id="wizardAlert" class="alert alert-danger" role="alert"></div>
            <div id="wizardInstructions">Welcome to the Yap Installer.  This tool was developed to help quickly construct your configuration with the basic settings.  You should refer to the documentation (<a target="_blank" href="https://bmlt.app/yap">https://bmlt.app/yap</a>) for adding additional settings after you have completed this process.

                Once you have completed filling out all the fields and they are confirmed to be correct, you will copy the text in the box below and paste into a file at the root of your yap folder called <pre>config.php</pre>

                Note: This wizard will not allow for upgrading from Yap 1.x, instead copy over your original configuration and refresh this page.</div>
            <div id="configuration">
                <?php foreach ($GLOBALS['required_config_settings'] as $setting) { ?>
                    <div class="input-group installerFieldSet">
                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <?php echo $setting ?>
                            </span>
                        </div>
                        <input value="" name="<?php echo $setting ?>" type="text" id="input_<?php echo $setting ?>" class="form-control" required>
                    </div>
                <?php } ?>
                <button type="submit" class="btn btn-primary" id="generateConfigButton">Generate Config</button>
            </div>
        </form>
    </div>
    <script src="dist/js/yap.min.js<?php isset($_REQUEST['JSDEBUG']) ? sprintf("?v=%s", time()) : "";?>"></script>
    <script type="text/javascript">
        var checkForConfigFileInterval;
        initInstaller();
    </script>
    <div class="modal fade" id="wizardResultModal" tabindex="-1" role="dialog" aria-labelledby="wizardResultModal" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    Configuration
                </div>
                <div class="modal-body">
                    <div class="card bg-light mb-3">
                        <div class="card-header">Copy and paste the below text into a file called <span class="codeFormat">config.php</span> at the root of your yap folder.  Please note any errors in red below.  If configured correctly, the page will navigate automatically to the login page.</div>
                        <div class="card-body">
                            <div id="result" class="card-text wizardResult"></div>
                        </div>
                    </div>
                    <div id="config-error-message"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

