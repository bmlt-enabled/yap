<?php include_once 'nav.php';

$service_bodies = admin_getServiceBodiesForUser();

echo var_dump($service_bodies);

include_once 'footer.php';



