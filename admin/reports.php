<?php require_once 'nav.php'; ?>
<div id="reports"></div>
<script type="text/javascript" src="js/moment-2.11.1.min.js"></script>
<script type="text/javascript" src="js/plotly-1.43.1.min.js"></script>
<?php require_once 'footer.php';

$actions = ['Volunteer', 'Meetings', 'Just For Today'];
$rows = getMetric()->fetchAll();
$plots = array();
foreach ($rows as $row) {
    $plots[json_decode($row['data'])->searchType][] = [
        'x' => $row['timestamp'],
        'y' => $row['counts']
    ];
}
?>
<script type="text/javascript">
    $(function() {
        var datasets = [];
        var plots = <?php echo json_encode($plots, true) ?>;
        var actions = <?php echo json_encode($actions, true) ?>;
        var colors = ['red', 'blue', 'green'];
        for (var a = 0; a < actions.length; a++) {
            var xAgg = [];
            var yAgg = [];
            for (var p = 0; p < plots[a+1].length; p++) {
                xAgg.push(plots[a+1][p].x);
                yAgg.push(plots[a+1][p].y);
            }

            datasets.push({
                type: 'scatter',
                mode: 'lines+markers',
                name: actions[a],
                x: xAgg,
                y: yAgg,
                line: { color: colors[a] }
            })
        }

        Plotly.newPlot("reports", datasets, {
            title: 'Usage Report',
            xaxis: {
                title: 'Day',
                type: 'date'
            },
            yaxis: {
                title: 'Occurrences'
            }
        });
    });
</script>
