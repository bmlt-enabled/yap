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
        <div class="button-group" role="group" id="cdr-table-controls">
            <button class="btn-sm btn-warning" id="print-table">Print</button>
            <button class="btn-sm btn-success" id="download-records-csv">CSV (Records)</button>
            <button class="btn-sm btn-success" id="download-events-csv">CSV (Events)</button>
            <button class="btn-sm btn-primary" id="download-xlsx">XLSX</button>
            <button class="btn-sm btn-secondary" id="download-json">JSON</button>
            <div id="refresh-button">
                <button class="btn-sm btn-dark" onclick="getReports();">Refresh</button>
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
    $(".page-size-dropdown-item").click(function(e) {
        $(".page-size-dropdown-item").removeClass("active");
        $(e.target).addClass("active");
        var pageSize = parseInt(e.target.text);
        table.setPageSize(pageSize);
    });

    $("#print-table").on("click", function(){
        table.print(false, true);
    });

    $("#download-records-csv").click(function(){
        table.download("csv", "yap-records.csv");
    });

    $("#download-events-csv").click(function(){
        eventsTable.download("csv", "yap-events.csv");
    });

    $("#download-json").click(function(){
        table.download("json", "yap.json");
    });

    $("#download-xlsx").click(function() {
        var sheets = {
            "Calls": true,
            "Events": "#events-table"
        };

        table.download("xlsx", "data.xlsx", {sheets:sheets});
    });

    var eventsTableColumns = [
        {title: "Event Time", field: "event_time", mutator: toCurrentTimezone},
        {title: "Event", field: "event_id"},
        {title: "Service Body Id", field: "service_body_id"},
        {title: "Metadata", field: "meta"},
        {title: "Parent CallSid", field: "parent_callsid", visible: false, download: true}
    ];

    var table = new Tabulator("#cdr-table", {
        layout: "fitColumns",
        responsiveLayout: "hide",
        tooltips: true,
        addRowPos: "top",
        history: true,
        pagination: "remote",
        paginationSize: 20,
        ajaxURL: "cdr_api.php",
        ajaxURLGenerator: function(url, config, params) {
            return url + "?service_body_id=" + $("#service_body_id").val() + "&page=" + params['page'] + "&size=" + params['size'];
        },
        ajaxResponse: function(url, params, response) {
            var events = [];
            for (var i = 0; i < response['data'].length; i++) {
                var callEvents = response['data'][i]['call_events'];
                for (var j = 0; j < callEvents.length; j++) {
                    var callEvent = callEvents[j];
                    events.push(callEvent);
                }
            }

            $(".subTableHolder").toggle();

            eventsTable.setData(events);
            return response;
        },
        pageLoaded: function(pageno) {
            $(".subTableHolder").hide();
        },
        movableColumns: true,
        resizableRows: true,
        printAsHtml: true,
        printHeader: "<h3>Call Detail Records<h3>",
        printFooter: "",
        rowClick: function(e, row) {
            $("#subTableId_" + row.getData().id).toggle();
        },
        initialSort: [
            {column:"start_time", dir:"desc"},
        ],
        columns: [
            {title:"Start Time", field:"start_time", mutator: toCurrentTimezone },
            {title:"End Time", field:"end_time", mutator: toCurrentTimezone },
            {title:"Duration (seconds)", field:"duration"},
            {title:"From", field:"from_number"},
            {title:"To", field:"to_number"},
            {title:"Call Events", field:"call_events", visible: false, download: true, formatter: function(cell, formatterParams, onRendered) {
                return JSON.stringify(cell.getValue());
            }}
        ],
        rowFormatter: function(row) {
            //create and style holder elements
            var holderEl = document.createElement("div");
            var tableEl = document.createElement("div");

            holderEl.style.boxSizing = "border-box";
            holderEl.style.padding = "10px 30px 10px 10px";
            holderEl.style.borderTop = "1px solid #333";
            holderEl.style.borderBotom = "1px solid #333";
            holderEl.style.background = "#ddd";
            holderEl.setAttribute('class', 'subTableHolder');
            holderEl.setAttribute('id', 'subTableId_' + row.getData().id);
            tableEl.style.border = "1px solid #333";
            tableEl.setAttribute('class', 'eventsSubtable');
            holderEl.appendChild(tableEl);
            row.getElement().appendChild(holderEl);

            var subTable = new Tabulator(tableEl, {
                layout: "fitColumns",
                data: row.getData().call_events,
                columns: eventsTableColumns
            });
        }
    });

    var eventsTable = new Tabulator("#events-table", {
        columns: eventsTableColumns,
        initialSort:[
            {column:"event_time", dir:"desc"},
        ],
    });
</script>
<script type="text/javascript">$(function(){getMetrics()})</script>
