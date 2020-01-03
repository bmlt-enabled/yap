<?php require_once 'nav.php';?>
<link rel="stylesheet" href="dist/css/yap-reports.min.css">
<div class="container">
    <select class="form-control form-control-sm" id="service_body_id" name="service_body_id">
        <option selected value="0">All</option>
        <?php
        $serviceBodies = getServiceBodyDetailForUser();
        sort_on_field($serviceBodies, 'name');
        foreach ($serviceBodies as $item) {?>
            <option value="<?php echo $item->id ?>"><?php echo $item->name ?> (<?php echo $item->id ?>)</option>
            <?php
        }?>
    </select>
    <div id="reports">
        <div id="metrics"></div>
        <div id="metrics-map"></div>
        <div class="button-group" role="group" id="cdr-table-controls">
            <button class="btn-sm btn-warning" id="print-table">Print</button>
            <button class="btn-sm btn-success" id="download-records-csv">CSV (Records)</button>
            <button class="btn-sm btn-success" id="download-events-csv">CSV (Events)</button>
            <button class="btn-sm btn-primary" id="download-xlsx">XLSX</button>
            <button class="btn-sm btn-secondary" id="download-json">JSON</button>
            <a class="btn-sm btn-secondary" id="metrics-json" target="_blank" href="metric_api.php?service_body_id=0">MetricsJSON</a>
            <a class="btn-sm btn-secondary" id="meetings-map-metrics-json" target="_blank" href="map_metric_api.php?service_body_id=0&format=csv&event_id=14">POI CSV (Meetings)</a>
            <a class="btn-sm btn-secondary" id="volunteers-map-metrics-json" target="_blank" href="map_metric_api.php?service_body_id=0&format=csv&event_id=1">POI CSV (Volunteers)</a>
            <div id="refresh-button-holder">
                <button id="refresh-button" class="btn-sm btn-dark">Refresh</button>
            </div>
            <div id="page-size-dropdown" class="btn-group">
                <button type="button" class="btn btn-sm btn-dark dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Page Size
                </button>
                <div class="dropdown-menu">
                    <a class="dropdown-item page-size-dropdown-item active" href="#">20</a>
                    <a class="dropdown-item page-size-dropdown-item" href="#">50</a>
                    <a class="dropdown-item page-size-dropdown-item" href="#">100</a>
                    <a class="dropdown-item page-size-dropdown-item" href="#">200</a>
                </div>
            </div>
        </div>
        <div id="cdr-table"></div>
        <div id="events-table" style="display:none;"></div>
    </div>
</div>
<?php require_once 'footer.php';?>
<script src="dist/js/yap-reports.min.js"></script>
<script type="text/javascript">
    $(function() {
        var table = initReports();
        getMetrics(table);
        drawMetricsMap();
    });
</script>
