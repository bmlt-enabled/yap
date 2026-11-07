<?php

namespace App\Simulator\Scenarios;

use App\Constants\CycleAlgorithm;
use App\Constants\DataType;
use App\Constants\VolunteerGender;
use App\Constants\VolunteerResponderOption;
use App\Constants\VolunteerRoutingType;
use App\Constants\VolunteerType;
use App\Models\ConfigData;
use App\Structures\ServiceBodyCallHandling;

/**
 * Writes a service body's call handling and volunteers straight to the database
 * of the app running yap:simulate --seed. That is the target's database only
 * when the simulator runs against itself (local, CI).
 *
 * Seeding replaces just that service body's call handling and volunteers and
 * leaves the rest of its configuration alone.
 */
class ScenarioSeeder
{
    /**
     * @param array<string, mixed> $callHandling fields stored in the call handling record,
     *                                           e.g. call_strategy, gender_routing, primary_contact
     * @param list<array<string, mixed>> $volunteers from volunteer()
     */
    public static function helpline(int $serviceBodyId, array $callHandling, array $volunteers): void
    {
        $record = new ServiceBodyCallHandling();
        $record->service_body_id = $serviceBodyId;
        $record->volunteer_routing = VolunteerRoutingType::VOLUNTEERS;
        $record->volunteer_routing_enabled = true;
        $record->call_strategy = CycleAlgorithm::LINEAR_CYCLE_AND_VOICEMAIL;
        foreach ($callHandling as $field => $value) {
            $record->$field = $value;
        }

        ConfigData::where('service_body_id', $serviceBodyId)
            ->whereIn('data_type', [DataType::YAP_CALL_HANDLING_V2, DataType::YAP_VOLUNTEERS_V2])
            ->delete();
        ConfigData::createServiceBodyCallHandling($serviceBodyId, $record);
        ConfigData::createVolunteers($serviceBodyId, $volunteers);
    }

    /**
     * A volunteer on shift all day, every day.
     *
     * @return array<string, mixed>
     */
    public static function volunteer(
        string $name,
        string $phoneNumber,
        int $gender = VolunteerGender::UNSPECIFIED,
        bool $responder = false,
        string $type = VolunteerType::PHONE,
    ): array {
        $shifts = [];
        foreach (range(1, 7) as $day) {
            $shifts[] = ['day' => $day, 'tz' => 'UTC', 'start_time' => '12:00 AM', 'end_time' => '11:59 PM', 'type' => $type];
        }

        return [
            'volunteer_name' => $name,
            'volunteer_phone_number' => $phoneNumber,
            'volunteer_gender' => $gender,
            'volunteer_responder' => $responder ? VolunteerResponderOption::ENABLED : VolunteerResponderOption::UNSPECIFIED,
            'volunteer_notes' => 'Seeded by php artisan yap:simulate',
            'volunteer_enabled' => true,
            'volunteer_shift_schedule' => base64_encode(json_encode($shifts)),
        ];
    }
}
