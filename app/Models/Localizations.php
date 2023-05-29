<?php

namespace App\Models;

class Localizations
{
    public $enUS;
    public $enAU;
    public $esUS;
    public $piglatin;
    public $ptBR;
    public $frCA;
    public $itIT;

    public function __construct() {}

    public function setLocalization($language, $localization): void {
        $this->{str_replace("-", "", $language)} = $localization;
    }

    public function &getLocalization($language) {
        return $this->{str_replace("-", "", $language)};
    }
}
