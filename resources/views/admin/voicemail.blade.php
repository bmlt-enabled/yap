<?php require_once 'nav.php';?>
<link rel="stylesheet" href="<?php echo url("/public/dist/css/yap-reports.min.css")?>">
<div class="container">
    <div class="alert alert-success" role="alert" style="display:none;" id="voicemail-deleted-alert">
        Saved.
    </div>
    <h3 class="voicemail-title"><?php
                                $service_body = getServiceBody($_REQUEST['service_body_id']);
                                echo sprintf("%s for %s", word('voicemail'), $service_body->name) ;?>
    </h3>
    <div id="voicemail-table"></div>
    <?php require_once 'footer.php';?>
    <script src="<?php echo url("/public/dist/js/yap-reports.min.js")?>"></script>
    <script type="text/javascript">
        var darkTheme = "<?php echo url("/public/dist/css/yap-tabulator-dark.min.css")?>";
        var lightTheme = "<?php echo url("/public/dist/css/yap-tabulator-dark.min.css")?>";
        loadTabulatorTheme();
        var data = <?php echo json_encode(getVoicemail($_REQUEST['service_body_id']))?>;
        var table = new Tabulator("#voicemail-table", {
            data: data,
            layout:"fitColumns",
            responsiveLayout:"hide",
            tooltips:true,
            addRowPos:"top",
            history: true,
            pagination:"local",
            paginationSize:20,
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
                {title:"Pin", field:"pin"},
                {formatter:function(cell, formatterParams) {
                        var actionString = "";
                        var row = cell.getRow();
                        var callsid = row.getData().callsid
                        var meta = row.getData().meta
                        if (meta != null) {
                            var voicemailLink = JSON.parse(meta)['url'].concat('.mp3');
                            actionString = "<button class=\"btn btn-sm btn-primary\" onclick=\"location.href='" + voicemailLink + "'\">Play</button> "
                        }
                        actionString += "<button class=\"btn btn-sm btn-danger\" onclick=\"deleteVoicemail('" + callsid + "')\">Delete</button>";
                        return actionString;
                    }},
            ],
            rowFormatter:function(row) {
                //create and style holder elements
                var holderEl = document.createElement("div");
                var tableEl = document.createElement("div");
                tableEl.style.border = "1px solid #333";
            }
        });
    </script>
