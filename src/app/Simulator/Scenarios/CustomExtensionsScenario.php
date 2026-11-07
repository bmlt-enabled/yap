<?php

namespace App\Simulator\Scenarios;

use App\Constants\SearchType;
use App\Simulator\SimulationFailed;
use App\Simulator\TwilioSimulator;

/**
 * A menu digit mapped to custom extensions sends the caller to custom-ext, which
 * plays the extensions greeting and gathers an extension; custom-ext-dialer
 * then dials the number configured for it, from the helpline's own number.
 */
class CustomExtensionsScenario extends BaseScenario
{
    private const MENU_DIGIT = '7';

    private const EXTENSION = '5';

    private const EXTENSION_NUMBER = '+15555550500';

    private const GREETING = 'https://example.org/yap-simulator/custom-extensions.mp3';

    public function name(): string
    {
        return 'custom-extensions';
    }

    public function description(): string
    {
        return 'Call: custom extension menu (custom-ext → custom-ext-dialer dials the extension)';
    }

    public function run(TwilioSimulator $simulator, ScenarioOptions $options): array
    {
        $call = $this->call($simulator, $options, [
            'override_digit_map_search_type' => [
                '1' => SearchType::VOLUNTEERS,
                '2' => SearchType::MEETINGS,
                self::MENU_DIGIT => SearchType::CUSTOM_EXTENSIONS,
            ],
            'override_custom_extensions' => [self::EXTENSION => self::EXTENSION_NUMBER],
            'override_en_US_custom_extensions_greeting' => self::GREETING,
        ])->press(self::MENU_DIGIT);

        $greeting = $call->pending('Gather')->child('Play')?->text;
        if ($greeting !== self::GREETING) {
            throw new SimulationFailed("Expected custom-ext to play the extensions greeting while gathering:\n" . $call->twiml()->raw);
        }

        $call->press(self::EXTENSION);
        $dial = $call->pending('Dial');
        if ($call->dialedNumbers() !== [self::EXTENSION_NUMBER] || !TwilioSimulator::sameNumber((string) $dial->attr('callerId'), $options->to)) {
            throw new SimulationFailed(sprintf(
                "Expected extension %s to dial %s with the helpline's caller ID %s:\n%s",
                self::EXTENSION,
                self::EXTENSION_NUMBER,
                $options->to,
                $call->twiml()->raw,
            ));
        }
        $call->hangUp();

        return [
            'menu digit ' . self::MENU_DIGIT . ' → custom-ext played the extensions greeting',
            sprintf('extension %s → dialed %s from %s', self::EXTENSION, self::EXTENSION_NUMBER, $options->to),
        ];
    }
}
