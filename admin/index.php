<?php
require_once 'header.php';
if ($_SERVER['REQUEST_URI'] == "/admin") {
    header('Location: /admin/', null, 301);
};

$state = UpgradeAdvisor::getState();
?>
<script type="text/javascript">
    if (!!window.MSInputMethodContext && !!document.documentMode) {
        document.write("<div style='text-align:center;'>Internet Explorer 11 is not supported.</div>");
    }

    function onAuthenticate() {
        $("#authenticateButton").attr("disabled", true);
        $("#auth").submit();
    }
</script>
<div id="signin" class="container">
    <div class="custom-control custom-switch" style="display: none;">
        <input type="checkbox" class="custom-control-input" id="darkSwitch" />
    </div>
    <form id="auth" class="form-signin" method="POST" action="auth_login.php">
        <div id="admin_title"><?php echo isset($GLOBALS['admin_title']) ? $GLOBALS['admin_title'] : ""; ?></div>
        <div id="yap-logo">
            <img src="dist/img/yap_logo.png" alt="Yap" width="310" height="100">
        </div>
        <div id="no-auth-message">
            <?php echo isset($_REQUEST['auth']) ? $GLOBALS['not_authorized'] : "" ?>
            <?php echo isset($_REQUEST['expired']) ? $GLOBALS['session_expired'] : "" ?>
        </div>
        <label for="inputEmail" class="sr-only"><?php echo $GLOBALS['username']?></label>
        <input name="username" type="username" id="inputUsername" class="form-control" placeholder="<?php echo $GLOBALS['username']?>" required autofocus>
        <label for="inputPassword" class="sr-only"><?php echo $GLOBALS['password']?></label>
        <input name="password" type="password" id="inputPassword" class="form-control" placeholder="<?php echo $GLOBALS['password']?>" required>
        <button id="authenticateButton" class="btn btn-lg btn-primary btn-block" onclick="onAuthenticate();"><?php echo $GLOBALS['authenticate']?></button>
        <select class="form-control" name="override_word_language" id="admin_language">
            <?php
            foreach ($available_languages as $key => $available_language) {
                ?>
                <option value="<?php echo $key; ?>"><?php echo $available_language; ?></option>
                <?php
            }
            ?>
        </select>
        <div id="version-info">
            <?php echo "v" . $state['version'] ?>
        </div>
    </form>
</div>
<?php
require_once 'footer.php';
