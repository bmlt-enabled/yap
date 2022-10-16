<?php
    require_once 'nav.php';
?>
<div class="container">
    <div class="row">
        <div class="col-md">
            <div class="jumbotron">
                <div class="home-title"><?php echo word('welcome')?>, <?php echo admin_GetUserName()?>...</div>
                <?php
                    $_REQUEST['include_warnings'] = "1";
                    require_once 'status_control.php';
                ?>
                <hr class="my-4">
                <div class="btn-group-lg">
                    <a target="_blank" class="btn btn-primary btn-md" href="https://bmlt.app/yap" role="button"><?php echo $GLOBALS['documentation']?></a>
                    <a target="_blank" class="btn btn-primary btn-md" href="https://github.com/bmlt-enabled/yap/issues" role="button"><?php echo $GLOBALS['bugs_requests']?></a>
                    <a target="_blank" class="btn btn-primary btn-md" href="https://github.com/bmlt-enabled/yap/blob/main/RELEASENOTES.md" role="button"><?php echo $GLOBALS['release_notes']?></a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once 'footer.php';?>
<script type="text/javascript">
    $(function() {
        $("#upgrade-advisor-details").tooltip();
    })
</script>
