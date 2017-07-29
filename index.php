<?php
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>
<Response>
    <Gather numDigits="5" timeout="10000" action="searchtype.php" method="GET">
	<Say>
            Hello, please enter your 5 digit zip code.
	</Say>
    </Gather>
</Response>
