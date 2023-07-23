<?php

use App\Repositories\ConfigRepository;
use App\Services\RootServerService;
use App\Services\SettingsService;
use App\Services\TwilioService;
use App\Constants\DataType;
use Tests\FakeTwilioHttpClient;
use Tests\RootServerMocks;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;

    $this->utility = setupTwilioService();
    //$this->rootServerMocks = new RootServerMocks();
});

test('initial sms gateway default', function () {
    // app()->instance(RootServerService::class, $this->rootServerMocks->getService());
    // mocking TwilioRestClient->messages->create()
    $messageListMock = mock('\Twilio\Rest\Api\V2010\Account\MessageList');
    $messageListMock->shouldReceive('create')
        ->with(is_string(""), is_array([]))->times(1);
    $this->utility->client->messages = $messageListMock;

    $repository = Mockery::mock(ConfigRepository::class);
    $repository->shouldReceive("getDbData")->with(
        '99',
        DataType::YAP_CALL_HANDLING_V2
    )->andReturn([(object)[
        "service_body_id" => "99",
        "id" => "200",
        "parent_id" => "43",
        "data" => "[{\"volunteer_routing\":\"volunteers_and_sms\",\"volunteers_redirect_id\":\"\",\"forced_caller_id\":\"\",\"call_timeout\":\"\",\"gender_routing\":\"0\",\"call_strategy\":\"1\",\"volunteer_sms_notification\":\"send_sms\",\"sms_strategy\":\"2\",\"primary_contact\":\"\",\"primary_contact_email\":\"\",\"moh\":\"\",\"override_en_US_greeting\":\"\",\"override_en_US_voicemail_greeting\":\"\"}]"
    ]])->once();
    app()->instance(ConfigRepository::class, $repository);

    $response = $this->call('GET', '/helpline-sms.php', [
        "OriginalCallerId" => '+19735551212',
        "To" => '+12125551212',
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
    ], false);
});
