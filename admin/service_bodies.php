<?php include_once 'nav.php';
$service_bodies = getServiceBodyDetailForUser();
?>
<div class="container">
    <div class="row">
        <div class="col-md">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th scope="col">Service Body</th>
                        <th scope="col">Route</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($service_bodies as $service_body) { ?>
                    <tr>
                        <td><?php echo $service_body->name ?></td>
                        <td><?php echo isset($service_body->helpline) ? $service_body->helpline : "NOT SET" ?></td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include_once 'footer.php';



