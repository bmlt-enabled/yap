{{--<?php $status = UpgradeAdvisor::getStatus(); ?>--}}
{{--<p class="lead">v<?php echo $status['version']?></p>--}}
{{--<a class="btn btn-sm btn-outline-<?php echo $status['status'] ? "success" : "danger" ?>"--}}
{{--   id="upgrade-advisor-details"--}}
{{--   data-toggle="tooltip"--}}
{{--   data-placement="bottom"--}}
{{--   target="_blank"--}}
{{--   href="<?php echo url(sprintf('/upgrade-advisor.php%s', isset($_REQUEST['run_exclude_errors_check']) ? "?run_exclude_errors_check=1" : "")) ?>"--}}
{{--   title="<?php echo $status['message']?>">--}}
{{--    <?php echo sprintf("Status: %s", ($status['status'] ? "OK" : "Problem")); ?>--}}
{{--</a>--}}
{{--<?php if (strlen($status['warnings']) > 0 && isset($_REQUEST['include_warnings'])) {
    ?>--}}
{{--<a class="btn btn-sm btn-outline-warning"--}}
{{--   id="upgrade-advisor-warnings"--}}
{{--   data-toggle="tooltip"--}}
{{--   data-placement="bottom"--}}
{{--   target="_blank"--}}
{{--   href="<?php echo url('/upgrade-advisor.php');  ?>"--}}
{{--   title="<?php echo $status['warnings']?>">Warnings</a>--}}
{{--
    <?php }?>--}}
