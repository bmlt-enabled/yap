<?php

namespace App\Services;

use App\Constants\Http;
use App\Constants\ReadingType;
use App\Utility\Sms;
use DOMDocument;
use DOMXPath;

class ReadingService
{
    protected HttpService $http;
    protected SettingsService $settings;

    public function __construct(HttpService $http, SettingsService $settings)
    {
        $this->http = $http;
        $this->settings = $settings;
    }

    public function get($reading = ReadingType::JFT, $sms = false): array
    {
        $d = new DOMDocument();
        $d->validateOnParse = true;
        $result = null;

        $wordLanguage = $this->settings->get('word_language');
        if ($wordLanguage == 'en-US' || $wordLanguage == 'en-AU') {
            $url = ($reading === ReadingType::JFT ? "https://www.jftna.org/jft/" : "https://www.spadna.org");
            $jft_language_dom_element = "table";
            $copyright_info = '';
            $preg_search_lang = "\r\n";
            $preg_replace_lang = "\n\n";
        } elseif ($wordLanguage == 'pt-BR' || $wordLanguage == 'pt-PT') {
            $url = 'http://www.na.org.br/meditacao';
            $jft_language_dom_element = '*[@class=\'content-home\']';
            $copyright_info = 'Todos os direitos reservados à: http://www.na.org.br';
            $preg_search_lang = "\r\n";
            $preg_replace_lang = "\n";
        } elseif ($wordLanguage == 'es-ES' || $wordLanguage == 'es-US') {
            $url = 'https://forozonalatino.org/sxh';
            $jft_language_dom_element = '*[@id=\'sx-wrapper\']';
            $copyright_info = 'Servicio del Foro Zonal Latinoamericano, Copyright 2017 NA World Services, Inc. Todos los Derechos Reservados.';
            $preg_search_lang = "\r\n\s";
            $preg_replace_lang = " ";
        } elseif ($wordLanguage == 'fr-FR' || $wordLanguage == 'fr-CA') {
            $url = 'https://jpa.narcotiquesanonymes.org';
            $jft_language_dom_element = '*[@class=\'contenu-principal\']';
            $copyright_info = 'Copyright (c) 2007-'.date("Y").', NA World Services, Inc. All Rights Reserved';
            $preg_search_lang = "\r\n";
            $preg_replace_lang = "\n\n";
        }

        $jft = new DOMDocument;
        libxml_use_internal_errors(true);
        $d->loadHTML(mb_convert_encoding($this->http->get($url, 3600)->body(), 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();
        libxml_use_internal_errors(false);
        $xpath = new DOMXpath($d);
        $body = $xpath->query("//$jft_language_dom_element");
        foreach ($body as $child) {
            $jft->appendChild($jft->importNode($child, true));
        }
        $result .= $jft->saveHTML();

        $stripped_results = strip_tags($result);
        $without_tabs     = str_replace("\t", "", $stripped_results);
        $trim_results     = trim($without_tabs);
        if ($sms) {
            $without_htmlentities = html_entity_decode($trim_results);
            $without_extranewlines = preg_replace("/[$preg_search_lang]+/", "$preg_replace_lang", $without_htmlentities);
            $message = Sms::chunkSplit($without_extranewlines);
            $finalMessage  = array();
            if (count($message) > 1) {
                for ($i = 0; $i < count($message); $i++) {
                    $jft_message = "(" .($i + 1). " of " .count($message). ")\n" .$message[$i];
                    $finalMessage[] = $jft_message;
                }
            } else {
                $finalMessage[] = $message;
            }
            return $finalMessage;
        } else {
            $final_array = explode("\n", preg_replace('/&(?!#?[a-z0-9]+;)/', '&amp;', html_entity_decode($trim_results, ENT_QUOTES, "UTF-8")));
            $final_array[] = $copyright_info;
            return $final_array;
        }
    }
}
