<?php include_once 'nav.php'?>
<div class="container">
    <div class="row">
        <div class="col-md">
            <div class="jumbotron">
                <h1 class="display-4"><?php echo $GLOBALS['welcome']?>, <?php echo $_SESSION['username']?>...</h1>
                <p class="lead">Yap (<?php echo $GLOBALS['version']?>)</p>
                <hr class="my-4">
                <a target="_blank" class="btn btn-primary btn-md" href="https://github.com/radius314/yap/blob/unstable/README.md" role="button"><?php echo $GLOBALS['documentation']?></a>
                <a target="_blank" class="btn btn-info btn-md" href="https://github.com/radius314/yap/issues" role="button"><?php echo $GLOBALS['bugs_requests']?></a>
                <a target="_blank" class="btn btn-secondary btn-md" href="https://github.com/radius314/yap/blob/unstable/RELEASENOTES.md" role="button"><?php echo $GLOBALS['release_notes']?></a>
            </div>
        </div>
    </div>
</div>
<?php include_once 'footer.php';
