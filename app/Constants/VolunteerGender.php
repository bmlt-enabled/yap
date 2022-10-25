<?php
namespace App\Constants;

class VolunteerGender
{
    const UNSPECIFIED = 0;
    const MALE = 1;
    const FEMALE = 2;
    const NO_PREFERENCE = 3;

    public static function getGenderById($genderId)
    {
        return match ($genderId) {
            VolunteerGender::MALE => "MALE",
            VolunteerGender::FEMALE => "FEMALE",
            default => "",
        };
    }
}
