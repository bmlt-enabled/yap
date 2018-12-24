<?php
require_once 'auth_verify.php';
require_once 'header.php';
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <a class="navbar-brand" href="#">Yap</a>
    <button class="navbar-toggler"
            type="button" data-toggle="collapse"
            data-target="#top-navbar"
            aria-controls="top-navbar"
            aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="top-navbar">
        <ul class="navbar-nav mr-auto">
            <?php
                $pages = array("Home", "Reports", "Service Bodies", "Schedules", "Settings", "Volunteers");
                foreach ($pages as $page) {
                    $slug = str_replace(" ", "_", strtolower($page))
                    ?>
            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == $slug.".php" ? "active" : ""?>">
                <a class="nav-link" href="<?php echo $slug?>.php"><?php echo $GLOBALS[$slug]?></a>
            </li>
            <?php }?>
        </ul>
        <ul class="nav justify-content-end">
            <li class="nav-item">
                <button type="button"
                        class="btn btn-danger"
                        id="log-out-button"
                        onclick="location.href='logout.php';"><?php echo $GLOBALS['log_out']?> <?php echo admin_GetUserName()?></button>
            </li>
        </ul>
    </div>
</nav>
