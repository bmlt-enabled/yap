<p class="lead">v{{ $status['version'] }}</p>
<a class="btn btn-sm btn-outline-{{ $status['status'] ? "success" : "danger" }}"
   id="upgrade-advisor-details"
   data-toggle="tooltip"
   data-placement="bottom"
   target="_blank"
   href="{{ url(sprintf('/upgrade-advisor%s', isset($_REQUEST['run_exclude_errors_check']) ? "?run_exclude_errors_check=1" : "")) }}"
   title="{{ $status['message'] }}">Status: {{ $status['status'] ? "OK" : "Problem" }}
</a>
<?php if (strlen($status['warnings']) > 0 && isset($_REQUEST['include_warnings'])) {?>
<a class="btn btn-sm btn-outline-warning"
   id="upgrade-advisor-warnings"
   data-toggle="tooltip"
   data-placement="bottom"
   target="_blank"
   href="{{ url('/upgrade-advisor') }}"
   title="{{ $status['warnings'] }}">Warnings</a>
<?php }?>
