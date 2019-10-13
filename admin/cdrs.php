<?php require_once 'nav.php';?>
<link rel="stylesheet" href="vendor/tabulator-tables/dist/css/tabulator.min.css">
<div class="container">
    <div class="button-group" role="group" id="cdr-table-controls">
        <button class="btn-sm btn-warning" id="print-table">Print</button>
        <button class="btn-sm btn-success" id="download-csv">CSV</button>
        <button class="btn-sm btn-primary" id="download-json">JSON</button>
    </div>
    <div id="cdr-table"></div>
</div>
<?php require_once 'footer.php';?>
<script src="vendor/tabulator-tables/dist/js/tabulator.min.js"></script>
<script type="text/javascript">

    var cdrs = <?php echo json_encode(adjustedCallRecords());?>

    $("#print-table").on("click", function(){
        table.print(false, true);
    });

    $("#download-csv").click(function(){
        table.download("csv", "data.csv");
    });

    $("#download-json").click(function(){
        table.download("json", "data.json");
    });

    var table = new Tabulator("#cdr-table", {
        data: cdrs,
        layout:"fitColumns",
        responsiveLayout:"hide",
        tooltips:true,
        addRowPos:"top",
        history:true,
        pagination:"local",
        paginationSize:20,
        movableColumns:true,
        resizableRows:true,
        printAsHtml:true,
        printHeader:"<h1>Call Detail Records<h1>",
        printFooter:"",
        initialSort:[
            {column:"start_time", dir:"desc"},
        ],
        columns:[
            {title:"Start Time", field:"start_time"},
            {title:"End Time", field:"end_time"},
            {title:"Duration (seconds)", field:"duration"},
            {title:"From", field:"from_number"},
            {title:"To", field:"to_number"},
            {title:"Call Events", field:"call_events", visible: false, download: true, formatter: function(cell, formatterParams, onRendered) {
                return JSON.stringify(cell.getValue());
            }}
        ],
        rowFormatter:function(row) {
            //create and style holder elements
            var holderEl = document.createElement("div");
            var tableEl = document.createElement("div");

            holderEl.style.boxSizing = "border-box";
            holderEl.style.padding = "10px 30px 10px 10px";
            holderEl.style.borderTop = "1px solid #333";
            holderEl.style.borderBotom = "1px solid #333";
            holderEl.style.background = "#ddd";

            tableEl.style.border = "1px solid #333";

            holderEl.appendChild(tableEl);

            row.getElement().appendChild(holderEl);

            var subTable = new Tabulator(tableEl, {
                layout: "fitColumns",
                data: row.getData().call_events,
                columns: [
                    {title: "Event Time", field: "event_time"},
                    {title: "Event", field: "event_id"},
                    {title: "Service Body Id", field: "service_body_id"},
                ]
            })
        }
    });
</script>
