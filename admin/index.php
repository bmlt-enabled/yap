<?php
require_once 'header.php';

if ($_SERVER['REQUEST_URI'] == "/admin") {
    header('Location: /admin/', null, 301);
};
?>
<div id="signin" class="container">
    <form class="form-signin" method="POST" action="auth_login.php">
        <h2 class="form-signin-heading">Yap</h2>
        <div id="no-auth-message">
            <?php echo isset($_REQUEST['auth']) ? $GLOBALS['not_authorized'] : "" ?>
            <?php echo isset($_REQUEST['expired']) ? $GLOBALS['session_expired'] : "" ?>
        </div>
        <label for="inputEmail" class="sr-only"><?php echo $GLOBALS['username']?></label>
        <input name="username" type="username" id="inputUsername" class="form-control" placeholder="<?php echo $GLOBALS['username']?>" required autofocus>
        <label for="inputPassword" class="sr-only"><?php echo $GLOBALS['password']?></label>
        <input name="password" type="password" id="inputPassword" class="form-control" placeholder="<?php echo $GLOBALS['password']?>" required>
        <button class="btn btn-lg btn-primary btn-block" type="submit"><?php echo $GLOBALS['authenticate']?></button>
        <select class="form-control" name="override_word_language" id="admin_language">
            <?php
            foreach ($available_languages as $key => $available_language) {
                ?>
                <option value="<?php echo $key; ?>"><?php echo $available_language; ?></option>
            <?php
            }
            ?>
        </select>
    </form>
</div>
<?php
require_once 'footer.php';
