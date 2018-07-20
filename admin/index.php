<?php
    include_once 'header.php';

    if ($_SERVER['REQUEST_URI'] == "/admin") {
        header('Location: /admin/', null, 301);
    };
?>
<div id="signin" class="container">
    <form class="form-signin" method="POST" action="auth_login.php">
        <h2 class="form-signin-heading">Yap</h2>
        <div id="no-auth-message"><?php echo isset($_REQUEST['auth']) ? $GLOBALS['not_authorized'] : "" ?></div>
        <label for="inputEmail" class="sr-only"><?php echo $GLOBALS['username']?></label>
        <input name="username" type="username" id="inputUsername" class="form-control" placeholder="<?php echo $GLOBALS['username']?>" required autofocus>
        <label for="inputPassword" class="sr-only"><?php echo $GLOBALS['password']?></label>
        <input name="password" type="password" id="inputPassword" class="form-control" placeholder="<?php echo $GLOBALS['password']?>" required>
        <button class="btn btn-lg btn-primary btn-block" type="submit"><?php echo $GLOBALS['authenticate']?></button>
    </form>
</div> <!-- /container -->
<?php
    include_once 'footer.php';
