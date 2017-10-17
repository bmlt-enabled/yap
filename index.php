<?php
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>
<Response>
    <Gather numDigits="1" timeout="10000" action="input-method.php" method="GET">
	<Say>
            Welcome to the North Carolina of Narcotics Anonymous Helpline.
	</Say>
        <Say>Press 1 to find someone to talk to.</Say>
        <Say>Press 2 to find a meeting.</Say>
    </Gather>
</Response>