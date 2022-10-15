<?php $status = UpgradeAdvisor::getStatus(); ?>
<p class="lead">v<?php echo $status['version']?></p>
<a class="btn btn-sm btn-outline-<?php echo $status['status'] ? "success" : "danger" ?>"
   id="upgrade-advisor-details"
   data-toggle="tooltip"
   data-placement="bottom"
   target="_blank"
   href="../upgrade-advisor.php"
   title="<?php echo $status['message']?>">
    <?php echo "Status: " . ($status['status'] ? "OK" : "Problem"); ?>
</a>
<?php if (strlen($status['warnings']) > 0 && isset($_REQUEST['include_warnings'])) {?>
    <a class="btn btn-sm btn-outline-warning"
       id="upgrade-advisor-warnings"
       data-toggle="tooltip"
       data-placement="bottom"
       target="_blank"
       href="../upgrade-advisor.php"
       title="<?php echo $status['warnings']?>">Warnings</a>
<?php }?>