<?php
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>
<Response>
    <Gather numDigits="5" timeout="10000" action="helpline-search.php" method="GET">
	<Say>
            Welcome to North Carolina of Narcotics Anonymous 
	</Say>
        <Say>Press 1 to find someone to talk to by county.</Say>
        <Say>Press 2 to find someone to talk to by city.</Say>
        <Say>Press 3 to find someone to talk to by zip code.</Say>
    </Gather>
</Response>