<?php require_once 'nav.php'; ?>
<div class="report-container">
    <canvas id="report"></canvas>
</div>
<script type="text/javascript" src="js/moment-2.11.1.min.js"></script>
<script type="text/javascript" src="js/chart-2.7.3.min.js"></script>
<?php require_once 'footer.php';

$actions = [1 => 'Volunteer', 'Meetings', 'Just For Today'];
$colors = [1 => 'red', 'blue', 'green'];

$rows = getMetric()->fetchAll();
$plots = array();
foreach ($rows as $row) {
    $plots[$row['searchType']][] = [
        'x' => $row['timestamp'],
        'y' => $row['counts']
    ];
}

$datasets = [];
for ($i = 1; $i <= count($plots); $i++) {
    $datasets[] = [
        'backgroundColor' => $colors[$i],
        'borderColor' => $colors[$i],
        'label' => $actions[$i],
        'fill' => false,
        'data' => $plots[$i]
    ];
}
?>
<script type="text/javascript">
    $(function() {
        var ctx = $("#report");
        var report = new Chart(ctx, {
            type: 'line',
            data: {
                datasets: <?php echo json_encode($datasets); ?>
            },
            options: {
                responsive: true,
                title: {
                    display: true,
                    text: 'Call Actions Report'
                },
                scales: {
                    xAxes: [{
                        type: 'time',
                        time: {
                            unit: 'day',
                            displayFormats: {'day': 'MM/DD/YY'},
                            tooltipFormat: 'MM/DD/YY',
                        },
                        display: true,
                        scaleLabel: {
                            display: true,
                            labelString: 'Date'
                        },
                        ticks: {
                            source: 'auto',
                            major: {
                                fontStyle: 'bold',
                                fontColor: '#FF0000'
                            }
                        }
                    }],
                    yAxes: [{
                        display: true,
                        beginAtZero: true,
                        min: 0,
                        scaleLabel: {
                            display: true,
                            labelString: 'Counts'
                        }
                    }]
                }
            }
        })
    });
</script>
