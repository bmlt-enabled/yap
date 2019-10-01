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
        paginationSize:20,         //allow 7 rows per page of data
        movableColumns:true,      //allow column order to be changed
        resizableRows:true,       //allow row order to be changed
        initialSort:[             //set the initial sort order of the data
            {column:"start_time", dir:"desc"},
        ],
        columns:[                 //define the table columns
            {title:"Start Time", field:"start_time"},
            {title:"End Time", field:"end_time"},
            {title:"Duration", field:"duration"},
            {title:"From", field:"from"},
            {title:"To", field:"to"},
        ],
    });
</script>
