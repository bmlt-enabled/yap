<?php include_once 'nav.php'?>
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
