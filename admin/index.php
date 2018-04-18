<?php
    include_once 'header.php';

    if ($_SERVER['REQUEST_URI'] == "/admin") {
        header('Location: /admin/', null, 301);
    };
?>
<link href="css/signin.css" rel="stylesheet">
<div class="container">

    <form class="form-signin" method="POST" action="api/authenticate.php">
        <h2 class="form-signin-heading">Yap</h2>
        <div id="no-auth-message"><?php echo isset($_REQUEST["auth"]) ? "Not Authorized" : "" ?></div>
        <label for="inputEmail" class="sr-only">Username</label>
        <input name="username" type="username" id="inputUsername" class="form-control" placeholder="Username" required autofocus>
        <label for="inputPassword" class="sr-only">Password</label>
        <input name="password" type="password" id="inputPassword" class="form-control" placeholder="Password" required>
        <button class="btn btn-lg btn-primary btn-block" type="submit">Authenticate</button>
    </form>

</div> <!-- /container -->
<?php
    include_once 'footer.php';
