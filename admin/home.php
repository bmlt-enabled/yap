<?php
    require_once 'nav.php';
    $status = UpgradeAdvisor::getStatus();
?>
<div class="container">
    <div class="row">
        <div class="col-md">
            <div class="jumbotron">
                <h1 class="display-5"><?php echo word('welcome')?>, <?php echo $_SESSION['username']?>...</h1>
                <p class="lead">Yap (<?php echo $GLOBALS['version']?>)</p>
                <button class="btn btn-sm <?php echo $status['status'] ? "btn-success" : "btn-danger" ?>"
                        id="upgrade-advisor-details"
                        data-toggle="tooltip"
                        data-placement="bottom"
                        title="<?php echo $status['message']?>">
                    <?php echo "Status: " . ($status['status'] ? "Operational" : "Problem"); ?>
                </button>
                <hr class="my-4">
                <div class="btn-group-lg">
                    <a target="_blank" class="btn btn-primary btn-md" href="https://github.com/radius314/yap/blob/master/README.md" role="button"><?php echo $GLOBALS['documentation']?></a>
                    <a target="_blank" class="btn btn-info btn-md" href="https://github.com/radius314/yap/issues" role="button"><?php echo $GLOBALS['bugs_requests']?></a>
                    <a target="_blank" class="btn btn-warning btn-md" href="https://github.com/radius314/yap/blob/master/RELEASENOTES.md" role="button"><?php echo $GLOBALS['release_notes']?></a>
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
