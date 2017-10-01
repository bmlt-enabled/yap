<?php
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>
<Response>
    <Gather numDigits="1" timeout="10000" action="zip-input.php" method="GET">
	<Say>
            Welcome to North Carolina of Narcotics Anonymous 
	</Say>
        <Say>Press 1 to find someone to talk to by zip code.</Say>
        <Say>Press 2 to find a meeting by zip code.</Say>
    </Gather>
</Response>