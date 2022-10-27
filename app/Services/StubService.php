<?php

namespace App\Services;

class StubService
{
    public static function timezone()
    {
        return [
            'dstOffset' => 0,
            'rawOffset' => -18000,
            'status' => 'OK',
            'timeZoneId' => 'America/New_York',
            'timeZoneName' => 'Eastern Standard Time'
        ];
    }

    public static function geocode()
    {
        return [
            'status' => 'OK',
            'results' => array([
                'address_components' => array([
                    'long_name' => '27592',
                    'short_name' => '27592',
                    'types' => array("postal_code")
                ],
                    [
                        'long_name' => 'Willow Spring',
                        'short_name' => 'Willow Spring',
                        'types' => array("neighborhood", "political")
                    ],
                    [
                        'long_name' => 'North Carolina',
                        'short_name' => 'NC',
                        'types' => array("administrative_area_level_1", "political")
                    ],
                    [
                        'long_name' => 'United States',
                        'short_name' => 'US',
                        'types' => array("country", "political")
                    ]),
                'formatted_address' => "Willow Spring, NC 27592, USA",
                'geometry' => [
                    'bounds' => [
                        'northeast' => ['lat' => 35.61496, 'lng' => -78.559837],
                        'southwest' => ['lat' => 35.5099279, 'lng' => -78.773051]
                    ],
                    'location' => ['lat' => 35.5648713, 'lng' => -78.6682395],
                    'location_type' => 'APPROXIMATE',
                    'viewport' => [
                        'northeast' => ['lat' => 35.61496, 'lng' => -78.559837],
                        'southwest' => ['lat' => 35.5099279, 'lng' => -78.773051]
                    ]
                ],
                'place_id' => 'ChIJ9_24SgGIrIkRjQxVxn7LHbk',
                'types' => array('postal_code')
            ])
        ];
    }
}
