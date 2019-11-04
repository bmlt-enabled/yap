<?php require_once 'nav.php';?>
<link rel="stylesheet" href="vendor/tabulator-tables/dist/css/tabulator.min.css">
<div class="container">
    <h3><?php
        $service_body = getServiceBody($_REQUEST['service_body_id']);
        echo sprintf("%s for %s", word('voicemail'), $service_body->name) ;?>
    </h3>
<div id="voicemail-table"></div>
<?php require_once 'footer.php';?>
<script src="vendor/tabulator-tables/dist/js/tabulator.min.js"></script>
<script type="text/javascript" src="vendor/moment/min/moment.min.js"></script>
<script type="text/javascript">
    var data = <?php echo json_encode(getVoicemail($_REQUEST['service_body_id']))?>;
    var table = new Tabulator("#voicemail-table", {
        data: data,
        layout:"fitColumns",
        responsiveLayout:"hide",
        tooltips:true,
        addRowPos:"top",
        history: true,
        pagination:"local",
        paginationSize:30,
        movableColumns:true,
        resizableRows:true,
        initialSort:[
            {column:"event_time", dir:"desc"},
        ],
        columns:[
            {title:"Timestamp", field:"event_time", mutator: toCurrentTimezone},
            {title:"CallSid", field:"callsid"},
            {title:"From", field:"from_number"},
            {title:"To", field:"to_number"},
            {title:"Action", field:"meta", formatter:"link",formatterParams:{
                target:"_blank",
                label:"Play",
            }, mutator: function(value, data, type, params, component) {
                return value != null ? JSON.parse(value)['url'] : null;
            }}
        ],
        rowFormatter:function(row) {
            //create and style holder elements
            var holderEl = document.createElement("div");
            var tableEl = document.createElement("div");
            tableEl.style.border = "1px solid #333";
        }
    });
</script>
