<?php require_once 'nav.php';?>
<div id="cdr-table"></div>
<?php require_once 'footer.php';?>
<script type="text/javascript">

    var cdrs = <?php echo json_encode(getCallRecords());?>

    var table = new Tabulator("#cdr-table", {
        data: cdrs,           //load row data from array
        layout:"fitColumns",      //fit columns to width of table
        responsiveLayout:"hide",  //hide columns that dont fit on the table
        tooltips:true,            //show tool tips on cells
        addRowPos:"top",          //when adding a new row, add it to the top of the table
        history:true,             //allow undo and redo actions on the table
        pagination:"local",       //paginate the data
        paginationSize:7,         //allow 7 rows per page of data
        movableColumns:true,      //allow column order to be changed
        resizableRows:true,       //allow row order to be changed
        initialSort:[             //set the initial sort order of the data
            {column:"name", dir:"asc"},
        ],
        columns:[                 //define the table columns
            {title:"Start Time", field:"start_time", editor:"input"},
            {title:"End Time", field:"end_time", align:"left"},
            {title:"Duration", field:"duration", width:95, editor:"select"},
            {title:"From", field:"from", align:"center", width:100},
            {title:"To", field:"to", width:130, editor:"input"},
        ],
    });
</script>
