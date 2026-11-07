<?php

namespace App\Listeners;

use App\Services\WebhookFailureService;
use Illuminate\Foundation\Http\Events\RequestHandled;

/**
 * Records every 5xx a Twilio-facing webhook returns (bmlt-enabled/yap#1686).
 *
 * Listening on RequestHandled rather than adding route middleware means a
 * failure is caught wherever it happens: in the controller, in the web group
 * middleware (CallBlocklist, SmsBlackhole), or in the global stack before the
 * route was even dispatched.
 */
class RecordWebhookFailure
{
    public function __construct(private WebhookFailureService $failures)
    {
    }

    public function handle(RequestHandled $event): void
    {
        $this->failures->record($event->request, $event->response);
    }
}
