<p class="lead">v{{ $status['version'] }}</p>
<a class="btn btn-sm btn-outline-{{ $status['status'] ? "success" : "danger" }}"
   id="upgrade-advisor-details"
   data-toggle="tooltip"
   data-placement="bottom"
   target="_blank"
   href="{{ url('/upgrade-advisor') }}"
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
