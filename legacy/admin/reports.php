<?php require_once 'nav.php';?>
<link rel="stylesheet" href="<?php echo url("/public/dist/css/yap-reports.min.css")?>">
<div class="container">
    <div id="reports-top-controls-container">
        <div id="reports-servicebodies-dropdown-container">
            <select class="form-control form-control-sm" id="service_body_id" name="service_body_id">
                <option selected value="-1">-= Select A Service Body =-</option>
                <?php if (isTopLevelAdmin()) { ?>
                <option value="0">All</option>
                <?php }
                $serviceBodies = getServiceBodiesForUser();
                sort_on_field($serviceBodies, 'name');
                foreach ($serviceBodies as $item) {?>
                    <option value="<?php echo $item->id ?>"><?php echo $item->name ?> (<?php echo $item->id ?>) / <?php echo $item->parent_name ?> (<?php echo $item->parent_id ?>)</option>
                    <?php
                }?>
            </select>
        </div>
        <div id="reports-servicebodies-recursive-container" class="custom-control custom-switch">
            <input type="checkbox" class="custom-control-input" id="recursive-reports-switch" />
            <label class="custom-control-label" for="recursive-reports-switch">Recurse</label>
        </div>
        <div id="reports-daterange-container">
            <div id="reportrange" style="background: #fff; cursor: pointer; padding: 5px 10px; border: 1px solid #ccc; width: 100%">
                🗓&nbsp;
                <span></span> ↓
            </div>
        </div>
    </div>
    <script type="text/javascript">
        var service_bodies = <?php echo json_encode(getServiceBodies()) ?>;
        function getServiceBodyById(id) {
            if (id === 0) return { id: "0", name: "General" };

            for (var i = 0; i < service_bodies.length; i++) {
                var service_body = service_bodies[i];
                if (parseInt(service_body["id"]) === id) {
                    return service_body;
                }
            }
        }
    </script>
    <div id="reports" style="display: none;">
        <div id="metrics"></div>
        <div id="metrics-summary">
            <button type="button" class="btn btn-sm btn-info">
                Volunteer Lookups (CALL) <span id="summary-volunteer-calls" class="badge badge-light">0</span>
            </button>
            <button type="button" class="btn btn-sm btn-info">
                Meeting Lookups (CALL) <span id="summary-meetingsearch-calls" class="badge badge-light">0</span>
            </button>
            <button type="button" class="btn btn-sm btn-danger">
                Missed (CALL) <span id="summary-missedvolunteer-calls" class="badge badge-light">0</span>
            </button>
            <button type="button" class="btn btn-sm btn-info">
                Volunteer Lookups (SMS) <span id="summary-volunteer-sms" class="badge badge-light">0</span>
            </button>
            <button type="button" class="btn btn-sm btn-info">
                Meeting Lookups (SMS) <span id="summary-meetingsearch-sms" class="badge badge-light">0</span>
            </button>
        </div>
        <div id="metrics-map"></div>
        <div class="button-group" role="group" id="cdr-table-controls">
            <button class="btn-sm btn-warning" id="print-table">Print</button>
            <button class="btn-sm btn-success" id="download-records-csv">CSV (Records)</button>
            <button class="btn-sm btn-success" id="download-events-csv">CSV (Events)</button>
            <button class="btn-sm btn-primary" id="download-xlsx">XLSX</button>
            <button class="btn-sm btn-warning" id="download-json">JSON</button>
            <a class="btn-sm btn-warning" id="metrics-json" target="_blank" href="../api/v1/reports/metrics?service_body_id=0">MetricsJSON</a>
            <a class="btn-sm btn-warning" id="meetings-map-metrics-json" target="_blank" href="../api/v1/reports/mapmetrics?service_body_id=0&format=csv&event_id=14">POI CSV (Meetings)</a>
            <a class="btn-sm btn-warning" id="volunteers-map-metrics-json" target="_blank" href="../api/v1/reports/mapmetrics?service_body_id=0&format=csv&event_id=1">POI CSV (Volunteers)</a>
            <div id="refresh-button-holder">
                <button id="refresh-button" class="btn-sm btn-dark">Refresh</button>
            </div>
        </div>
        <div id="cdr-table"></div>
        <div id="events-table" style="display:none;"></div>
    </div>
</div>
<script src="<?php echo url("/public/dist/js/yap-reports.js")?>"></script>
<?php require_once 'footer.php';?>
<script type="text/javascript" src="<?php echo url("/public/dist/js/daterangepicker.js") ?>"></script>
<link rel="stylesheet" type="text/css" href="<?php echo url("/public/dist/css/daterangepicker.css") ?>" />
<script type="text/javascript">
    var darkTheme = "<?php echo url("/public/dist/css/yap-tabulator-dark.min.css")?>";
    var lightTheme = "<?php echo url("/public/dist/css/yap-tabulator-dark.min.css")?>";
    var meetingsMarker = "<?php echo url("/public/dist/img/green_marker.png") ?>";
    var volunteersMarker = "<?php echo url("/public/dist/img/orange_marker.png") ?>";

    var start = moment().subtract(29, 'days');
    var end = moment();

    function cb(start, end, label, init) {
        $('#reportrange span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
        if (!init) updateAllReports();
    }

    $('#reportrange').daterangepicker({
        startDate: start,
        endDate: end,
        ranges: {
            'Today': [moment(), moment()],
            'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
            'Last 60 Days': [moment().subtract(59, 'days'), moment()],
            'Last 90 Days': [moment().subtract(89, 'days'), moment()],
            'This Month': [moment().startOf('month'), moment().endOf('month')],
            'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        }
    }, cb);

    cb(start, end, "", true);

    $(function() {
        loadTabulatorTheme();
        var table = initReports(function(data) {
            drawMetricsMap(data);
        });
        getMetrics(table);
    });
</script>
