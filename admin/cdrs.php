<?php require_once 'nav.php';?>
<div class="container">
    <div class="button-group" role="group" id="cdr-table-controls">
        <button class="btn-sm btn-warning" id="print-table">Print</button>
        <button class="btn-sm btn-success" id="download-csv">CSV</button>
        <button class="btn-sm btn-primary" id="download-json">JSON</button>
    </div>
    <div id="cdr-table"></div>
</div>
<?php require_once 'footer.php';?>
<script type="text/javascript">

    var cdrs = <?php echo json_encode(getCallRecords());?>

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
            {title:"From", field:"from"},
            {title:"To", field:"to"},
        ],
    });
</script>
