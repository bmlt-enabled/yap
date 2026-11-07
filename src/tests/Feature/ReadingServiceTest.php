<?php

use App\Constants\ReadingType;
use App\Services\MeditationSource;
use App\Services\ReadingService;
use App\Services\SettingsService;
use FetchMeditation\JFTLanguage;
use FetchMeditation\SPADLanguage;
use Illuminate\Support\Facades\Log;

// ReadingService runs for real here. Only the transport underneath
// fetch-meditation is faked: Tests\Fakes\FakeMeditationSource serves the pages
// recorded in tests/Fixtures/http/readings (bound for every test in Pest.php).

beforeEach(function () {
    $this->settings = new SettingsService();
    app()->instance(SettingsService::class, $this->settings);
    $this->source = app(MeditationSource::class);
});

function readingService(): ReadingService
{
    return app(ReadingService::class);
}

const JFT_LINES = [
    'November 07, 2026',
    "Feeling God's will",
    'Page 325',
    "\"I sincerely believed that a Higher Power could restore my sanity and that I would stop trying to figure out what God's will was, just accept things for what they were, and be grateful.\"",
    '.',
    'The longer we stay clean, the less surely we "know" what our Higher Power\'s will for us is--and the less it matters.',
    'After staying clean a few years, what we do seem to know is when we are acting against God\'s will for us.',
    'Our Eleventh Step clearly states the true goal of prayer and meditation',
    "Just for Today: I will pray for the knowledge of my Higher Power's will for me and the power to carry it out.",
];

const SPAD_LINES = [
    'November 07, 2026',
    'Purpose and Our Path Forward',
    'Page 322',
    "\"Faith in the process means believing that we are moving in the right direction, even if it's not where we thought we would be going.\"",
    'Living Clean, Chapter 1, "Desperation to Passion"',
    'On the day of our first NA meeting, we may have predictions about what our lives will look like without drugs',
    'Whatever questions we have about finding our true purpose',
    'We find purpose (or many purposes, as time passes) in NA',
    "I can't predict the future, but I believe I have one.",
];

/**
 * Each expected line is a prefix of the line at the same position.
 */
function expectReadingLines(array $lines, array $expectedPrefixes): void
{
    // the expected lines, then a copyright line
    expect($lines)->toHaveCount(count($expectedPrefixes) + 1);
    foreach ($expectedPrefixes as $i => $prefix) {
        expect($lines[$i])->toStartWith($prefix);
    }
    expect(end($lines))->toContain('NA World Services, Inc. All Rights Reserved');
}

test('the JFT is parsed from the recorded page into the lines that are read out', function () {
    $lines = readingService()->get(ReadingType::JFT);

    expectReadingLines($lines, JFT_LINES);
    expect($this->source->fetchedUrls)->toBe(['https://jft.na.org']);
});

test('the SPAD is parsed from the recorded page into the lines that are read out', function () {
    $lines = readingService()->get(ReadingType::SPAD);

    expectReadingLines($lines, SPAD_LINES);
    // the row of dashes between the reading and the thought is not read out
    expect(implode(' ', $lines))->not->toContain('———');
    expect($this->source->fetchedUrls)->toBe(['https://spad.na.org']);
});

test('markup and entities in the reading body are stripped before it is spoken', function () {
    $this->source->respond('https://jft.na.org', str_replace(
        'The longer we stay clean',
        '<b>The</b> longer&nbsp;we stay &amp; clean',
        $this->source->responses['https://jft.na.org']
    ));

    $lines = readingService()->get(ReadingType::JFT);

    expect($lines[5])->toStartWith("The longer\u{a0}we stay & clean");
});

test('the SMS flag returns the same lines as voice', function (int $reading) {
    expect(readingService()->get($reading, true))->toBe(readingService()->get($reading, false));
})->with([
    'JFT' => ReadingType::JFT,
    'SPAD' => ReadingType::SPAD,
]);

test('the JFT is read out over voice', function ($method) {
    $response = $this->call($method, '/fetch-jft.php');

    $response
        ->assertStatus(200)
        ->assertHeader('Content-Type', 'text/xml; charset=utf-8')
        ->assertSeeInOrder([
            '<Say voice="alice" language="en-US">November 07, 2026</Say>',
            '<Say voice="alice" language="en-US">Feeling God\'s will</Say>',
            '<Say voice="alice" language="en-US">Page 325</Say>',
            'Just for Today: I will pray',
            'NA World Services, Inc. All Rights Reserved</Say>',
        ], false);
    expect(substr_count($response->getContent(), '<Say '))->toBe(count(JFT_LINES) + 1);
})->with(['GET', 'POST']);

test('the SPAD is read out over voice', function ($method) {
    $response = $this->call($method, '/fetch-spad.php');

    $response
        ->assertStatus(200)
        ->assertHeader('Content-Type', 'text/xml; charset=utf-8')
        ->assertSeeInOrder([
            '<Say voice="alice" language="en-US">November 07, 2026</Say>',
            '<Say voice="alice" language="en-US">Purpose and Our Path Forward</Say>',
            'Living Clean, Chapter 1',
            "I can't predict the future",
            'NA World Services, Inc. All Rights Reserved</Say>',
        ], false);
    expect(substr_count($response->getContent(), '<Say '))->toBe(count(SPAD_LINES) + 1);
})->with(['GET', 'POST']);

test('the reading is spoken in the configured voice and language', function () {
    // the per-language voice, which wins over `voice`
    $this->settings->set('en_AU_voice', 'Polly.Joanna');
    $this->settings->set('language', 'en-AU');

    $this->get('/fetch-jft.php')
        ->assertStatus(200)
        ->assertSee('<Say voice="Polly.Joanna" language="en-AU">November 07, 2026</Say>', false);
});

test('word_language picks the JFT edition', function (string $wordLanguage, JFTLanguage $edition) {
    $this->settings->set('word_language', $wordLanguage);

    readingService()->get(ReadingType::JFT);

    expect($this->source->requested)->toBe([$edition]);
})->with([
    ['en-US', JFTLanguage::English],
    ['en-AU', JFTLanguage::English],
    ['pt-BR', JFTLanguage::Portuguese],
    ['pt-PT', JFTLanguage::Portuguese],
    ['es-US', JFTLanguage::Spanish],
    ['es-ES', JFTLanguage::Spanish],
    ['fr-CA', JFTLanguage::French],
    ['fr-FR', JFTLanguage::French],
]);

test('the SPAD is only published in English, whatever word_language is', function (string $wordLanguage) {
    $this->settings->set('word_language', $wordLanguage);

    readingService()->get(ReadingType::SPAD);

    expect($this->source->requested)->toBe([SPADLanguage::English]);
})->with(['en-US', 'en-AU', 'pt-BR', 'pt-PT', 'es-US', 'es-ES', 'fr-CA', 'fr-FR']);

test('a word_language with no reading edition is rejected', function (int $reading) {
    $this->settings->set('word_language', 'it-IT');

    expect(fn () => readingService()->get($reading))
        ->toThrow(InvalidArgumentException::class, 'Unsupported language: it-IT');
    expect($this->source->fetchedUrls)->toBe([]);
})->with([
    'JFT' => ReadingType::JFT,
    'SPAD' => ReadingType::SPAD,
]);

test('an unknown reading type is rejected', function () {
    expect(fn () => readingService()->get(99))
        ->toThrow(InvalidArgumentException::class, 'Invalid reading type: 99');
});

test('when the primary feed is down the reading comes from the fallback source', function (string $reading, int $type, array $expected) {
    [$primary, $fallback] = $this->source->sourcesFor($reading);
    $this->source->respond($primary, 503);

    $lines = readingService()->get($type);

    expectReadingLines($lines, $expected);
    expect($this->source->fetchedUrls)->toBe([$primary, $fallback]);
})->with([
    'JFT' => ['jft', ReadingType::JFT, JFT_LINES],
    'SPAD' => ['spad', ReadingType::SPAD, SPAD_LINES],
]);

dataset('feed outages', [
    'non-200' => 503,
    'not found' => 404,
    'empty body' => '',
    'whitespace body' => "  \n ",
    'page without the reading table' => '<html><body><p>Down for maintenance</p></body></html>',
]);

test('a JFT outage yields no lines instead of an error', function (string|int $response) {
    Log::spy();
    $this->source->failAll('jft', $response);

    expect(readingService()->get(ReadingType::JFT))->toBe([]);

    expect($this->source->fetchedUrls)->toBe($this->source->sourcesFor('jft'));
    Log::shouldHaveReceived('warning')->once()
        ->withArgs(fn ($message, $context) => $message === 'Could not fetch the JFT reading'
            && str_contains($context['response'], 'all_sources_failed'));
})->with('feed outages');

test('a SPAD outage yields no lines instead of an error', function (string|int $response) {
    Log::spy();
    $this->source->failAll('spad', $response);

    expect(readingService()->get(ReadingType::SPAD))->toBe([]);

    expect($this->source->fetchedUrls)->toBe($this->source->sourcesFor('spad'));
    Log::shouldHaveReceived('warning')->once()
        ->withArgs(fn ($message, $context) => $message === 'Could not fetch the SPAD reading'
            && str_contains($context['response'], 'all_sources_failed'));
})->with('feed outages');

test('a JFT outage answers the voice webhook with no content', function () {
    $this->source->failAll('jft', 503);

    $this->get('/fetch-jft.php')->assertNoContent();
});

test('a SPAD outage answers the voice webhook with an empty response', function () {
    $this->source->failAll('spad', 503);

    $response = $this->get('/fetch-spad.php');

    $response->assertStatus(200)->assertHeader('Content-Type', 'text/xml; charset=utf-8');
    expect($response->getContent())->not->toContain('<Say');
    expect($response->getContent())->toContain('<Response/>');
});
