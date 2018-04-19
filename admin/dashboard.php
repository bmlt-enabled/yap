<?php
include_once 'auth_verify.php';
include_once 'header.php';
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
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
            <li class="nav-item active">
                <a class="nav-link" href="#"><?php echo $GLOBALS['home_link']?><span class="sr-only">(current)</span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#"></a>
            </li>
        </ul>
        <ul class="nav justify-content-end">
            <li class="nav-item">
                <button type="button"
                        class="btn btn-danger"
                        id="log-out-button"
                        onclick="location.href='logout.php';"><?php echo $GLOBALS['log_out']?></button>
            </li>
        </ul>
    </span>
    </div>
</nav>
<div class="container">
    <div class="row">
        <div class="col-md">
            <div class="jumbotron">
                <h1 class="display-4"><?php echo $GLOBALS['welcome']?></h1>
                <p class="lead"><?php echo $GLOBALS['welcome_to_yap']?></p>
                <hr class="my-4">
                <p><?php echo $GLOBALS['introduction']?></p>
                <a target="_blank" class="btn btn-primary btn-md" href="https://github.com/radius314/yap/blob/master/README.md" role="button"><?php echo $GLOBALS['documentation']?></a>
                <a target="_blank" class="btn btn-secondary btn-md" href="https://github.com/radius314/yap/blob/master/RELEASENOTES.md" role="button"><?php echo $GLOBALS['release_notes']?></a>
            </div>
        </div>
    </div>
</div>
<?php include_once 'footer.php';
