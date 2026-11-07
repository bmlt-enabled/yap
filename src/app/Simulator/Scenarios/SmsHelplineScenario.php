<?php

namespace App\Simulator\Scenarios;

use App\Constants\CycleAlgorithm;
use App\Constants\VolunteerRoutingType;
use App\Constants\VolunteerType;
use App\Services\RootServerService;
use App\Services\SettingsService;
use App\Simulator\SimulationFailed;
use App\Simulator\TwilioSimulator;

/**
 * Texting the helpline keyword with a location ("talk 14456") routes by SMS:
 * yap finds the service body covering the location, confirms to the texter, and
 * texts that body's SMS volunteers the texter's number. With the blasting SMS
 * strategy every SMS volunteer is notified, and a phone-only volunteer is not.
 * The keyword alone gets instructions back.
 *
 * SMS routing goes by coverage, not --service-body, so --seed configures
 * whichever service body the BMLT root server says covers --latitude/--longitude
 * (volunteers_and_sms routing).
 */
class SmsHelplineScenario extends BaseScenario
{
    private const SMS_VOLUNTEERS = ['+15555550401', '+15555550402'];

    private const PHONE_VOLUNTEER = '+15555550403';

    private const KEYWORD = 'talk';

    protected bool $needsSeed = true;

    public function name(): string
    {
        return 'sms-helpline';
    }

    public function description(): string
    {
        return 'SMS: "talk <location>" routes to the covering service body\'s SMS volunteers (volunteers_and_sms)';
    }

    public function run(TwilioSimulator $simulator, ScenarioOptions $options): array
    {
        $this->requireOptions($options);
        $serviceBodyId = $this->coveringServiceBody($options);
        ScenarioSeeder::helpline($serviceBodyId, [
            'volunteer_routing' => VolunteerRoutingType::VOLUNTEERS_AND_SMS,
            'sms_strategy' => CycleAlgorithm::BLASTING,
        ], [
            ScenarioSeeder::volunteer('SMS Volunteer One', self::SMS_VOLUNTEERS[0], type: VolunteerType::SMS),
            ScenarioSeeder::volunteer('SMS Volunteer Two', self::SMS_VOLUNTEERS[1], type: VolunteerType::SMS),
            ScenarioSeeder::volunteer('Phone Volunteer', self::PHONE_VOLUNTEER, type: VolunteerType::PHONE),
        ]);

        $mark = $simulator->lastOutboundId();
        $simulator->sendSms($options->from, $options->to, self::KEYWORD . ' ' . $options->location, $options->locationOverrides());

        $reply = (string) $this->onlyText($simulator, $mark, $options->from, 'confirmation to the texter')['data']['Body'];
        self::expectContains($reply, 'your request has been received', 'the reply to the texter');
        foreach (self::SMS_VOLUNTEERS as $volunteer) {
            $text = $this->onlyText($simulator, $mark, $volunteer, 'SMS volunteer notification');
            self::expectContains((string) $text['data']['Body'], 'someone is requesting SMS help from ' . $options->from, "the notification to {$volunteer}");
            if (!TwilioSimulator::sameNumber((string) ($text['data']['From'] ?? ''), $options->to)) {
                throw new SimulationFailed("Expected the notification to {$volunteer} to come from the helpline {$options->to}, it came from " . ($text['data']['From'] ?? '?'));
            }
        }
        if ($simulator->texts($mark, self::PHONE_VOLUNTEER) !== []) {
            throw new SimulationFailed('A phone-only volunteer (' . self::PHONE_VOLUNTEER . ') was texted an SMS helpline request.');
        }

        $mark = $simulator->lastOutboundId();
        $simulator->sendSms($options->from, $options->to, self::KEYWORD, $options->locationOverrides());
        $help = (string) $this->onlyText($simulator, $mark, $options->from, 'keyword without a location')['data']['Body'];
        self::expectContains($help, 'please send a message formatting as ' . self::KEYWORD, 'the reply to a bare keyword');

        return [
            "\"talk {$options->location}\" → service body {$serviceBodyId} → texter told the request was received",
            'both SMS volunteers texted the texter\'s number; the phone-only volunteer was not',
            '"talk" alone → texter told how to format the request',
        ];
    }

    /**
     * The service body yap's SMS routing will pick for the scenario location:
     * the first helpline search result the root server knows as a service body
     * (MeetingResultsService::getServiceBodyCoverage).
     */
    private function coveringServiceBody(ScenarioOptions $options): int
    {
        $rootServer = app(RootServerService::class);
        $settings = app(SettingsService::class);
        $results = $rootServer->helplineSearch(
            $options->latitude,
            $options->longitude,
            $settings->get('helpline_search_radius'),
            $settings->get('call_routing_filter'),
        );
        $known = array_map(fn ($body) => (int) $body->id, $rootServer->getServiceBodiesForRouting($options->latitude, $options->longitude) ?? []);

        foreach (is_array($results) ? $results : [] as $result) {
            if (in_array((int) $result->service_body_bigint, $known, true)) {
                return (int) $result->service_body_bigint;
            }
        }

        throw new SimulationFailed("The BMLT root server has no service body covering {$options->latitude},{$options->longitude} to route SMS to.");
    }
}
