<?php require_once 'nav.php';?>
<link rel="stylesheet" href="/dist/css/yap-reports.min.css">
<div class="container">
    <div id="reports-top-controls-container">
        <div id="reports-servicebodies-dropdown-container">
            <select class="form-control form-control-sm" id="service_body_id" name="service_body_id">
                <option selected value="-1">-= Select A Service Body =-</option>
                <option value="0">All</option>
                <?php
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
            <label class="custom-control-label" for="recursive-reports-switch">Select Recursively</label>
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
                    <a class="dropdown-item page-size-dropdown-item active" href="#">50</a>
                    <a class="dropdown-item page-size-dropdown-item" href="#">100</a>
                    <a class="dropdown-item page-size-dropdown-item" href="#">200</a>
                    <a class="dropdown-item page-size-dropdown-item" href="#">500</a>
                </div>
            </div>
        </div>
        <div id="cdr-table"></div>
        <div id="events-table" style="display:none;"></div>
    </div>
</div>
<?php require_once 'footer.php';?>
<script src="/dist/js/yap-reports.min.js"></script>
<script type="text/javascript">
    $(function() {
        loadTabulatorTheme();
        var table = initReports();
        getMetrics(table);
        drawMetricsMap();
    });
</script>
