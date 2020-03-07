<?php
require_once __DIR__ . '/../endpoints/_includes/constants.php';
require_once 'spinner_dialog.php';
if (file_exists('../config.php')) {
    header('Location: /admin');
    exit();
}
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
            <div id="yap-logo">
                <img src="dist/img/yap_logo.png" alt="Yap" width="310" height="100">
            </div>
            <div id="no-auth-message"></div>
            <div id="wizardTitle">Wizard</div>
            <div id="wizardInstructions">Welcome to the Yap Wizard.  This tool was developed to help quickly construct your configuration with the basic settings.  You should refer to the documentation (<a target="_blank" href="https://yap.bmlt.app">https://yap.bmlt.app</a>) for adding additional settings after you have completed this process.

                Once you have completed filling out all the fields and they are confirmed to be correct, you will copy the text in the box below and paste into a file at the root of your yap folder called <pre>config.php</pre>

                Note: This wizard will not allow for upgrading from Yap 1.x, instead copy over your original configuration and refresh this page.</div>
            <div id="configuration">
                <?php foreach ($GLOBALS['settings'] as $setting) { ?>
                    <div class="installerFieldSet">
                        <label for="input_<?php echo $setting ?>" class="sr-only"><?php echo $setting ?></label>
                        <input value="https://bmlt.sezf.org/main_server" name="<?php echo $setting ?>" type="text" id="input_<?php echo $setting ?>" class="form-control" placeholder="<?php echo $setting ?>" required autofocus>
                    </div>
                <?php } ?>
                <button type="submit" class="btn btn-primary" id="generateConfigButton">Generate Config</button>
            </div>
        </form>
    </div>
    <script src="dist/js/yap.min.js<?php isset($_REQUEST['JSDEBUG']) ? sprintf("?v=%s", time()) : "";?>"></script>
    <script type="text/javascript">
        jQuery(".wizardForm").on("submit", function(event) {
            event.preventDefault();
            spinnerDialog(true, "Validating configuration", function() {
                generateConfig(function(config) {
                    spinnerDialog(false, '', function() {
                        jQuery("#result").html(config);
                        $("#wizardResultModal").modal('show');
                    });
                });
            });
        });

        function generateConfig(callback) {
            var bmltRootServer = jQuery("#input_bmlt_root_server").val();
            jQuery.getJSON(bmltRootServer + "/client_interface/jsonp/?switcher=GetServerInfo&callback=?", function (data) {
                if (data == null) {
                    setError("Root server is incorrect or unavailable.");
                    return;
                }

                var configHtml = "<?php echo htmlspecialchars("<?php") ?><br/>";
                if (parseFloat(data[0]['version']) >= parseFloat("2.14")) {
                    var fields = jQuery('[id*="input_"]');
                    for (var i = 0; i < fields.length; i++) {
                        var field = fields[i];
                        configHtml += "static $" + field.id.replace("input_", "") + " = \"" + field.value + "\";<br/>";
                    }
                }

                callback(configHtml);
            });
        }

        function setError(message) {
            jQuery("#no-auth-message").html(message);
        }
    </script>
    <div class="modal fade" id="wizardResultModal" tabindex="-1" role="dialog" aria-labelledby="wizardResultModal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    Config
                </div>
                <div class="modal-body">
                    <div id="result" style="font-family: courier; font-size: 10px; border-color: black; border-style: dot-dot-dash"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

