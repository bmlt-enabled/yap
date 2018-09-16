<?php
include 'functions.php';

header("content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

$d = new DOMDocument();
$d->validateOnParse = true;
$result = null;

if (setting('word_language') == 'en-US') {
    $url = 'http://www.jftna.org/jft/';
    $jft_language_dom_element = "table";
    $copyright_info = '';
} else if (setting('word_language') == 'pt-BR' || setting('word_language') == 'pt-PT') {
    $url = 'http://www.na.org.br/meditacao';
    $jft_language_dom_element = '*[@class=\'content-home\']';
    $copyright_info = 'Todos os direitos reservados à: http://www.na.org.br';
} else if (setting('word_language') == 'es-ES') {
    $url = 'https://forozonalatino.org/sxh';
    $jft_language_dom_element = '*[@id=\'sx-wrapper\']';
    $copyright_info = 'Servicio del Foro Zonal Latinoamericano, Copyright 2017 NA World Services, Inc. Todos los Derechos Reservados.';
} else if (setting('word_language') == 'fr-FR') {
    $url = 'https://jpa.narcotiquesanonymes.org';
    $jft_language_dom_element = '*[@class=\'contenu-principal\']';
    $copyright_info = 'Copyright (c) 2007-'.date("Y").', NA World Services, Inc. All Rights Reserved';
}



$jft = new DOMDocument;
libxml_use_internal_errors(true);
$d->loadHTML(get($url));
libxml_clear_errors();
libxml_use_internal_errors(false);
$xpath = new DOMXpath($d);
$body = $xpath->query("//$jft_language_dom_element");
foreach ($body as $child) {
    $jft->appendChild($jft->importNode($child, true));
}
$result .= $jft->saveHTML();

$stripped_results = strip_tags( $result );
$without_tabs     = str_replace( "\t", "", $stripped_results );
$final_array      = explode( "\n", $without_tabs );
array_push($final_array, $copyright_info);
?>
<Response>
    <?php
        foreach ($final_array as $item)  {
            if (trim($item) != "") {
                echo "<Say voice=\"" . setting('voice') . "\" language=\"" . setting('language') . "\">"
                     . html_entity_decode($item, null, "UTF-8") . "</Say>";
            }
        }
    ?>
    <Hangup />
</Response>
