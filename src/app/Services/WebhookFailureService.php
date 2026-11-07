<?php

namespace App\Services;

use App\Constants\EventId;
use App\Models\RecordEvent;
use App\Models\WebhookFailure;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Records and reports runtime failures of the Twilio-facing webhooks
 * (bmlt-enabled/yap#1686).
 *
 * When a webhook 500s, Twilio plays "an application error has occurred" to the
 * caller and nobody at the service body knows unless they complain. Every 5xx
 * on a twilio.signature route is written to webhook_failures so System Health
 * and the Dashboard can show it.
 *
 * Privacy: a row holds the route path (never the query string), the CallSid
 * (which call records already store) and the exception. Anything the caller
 * sent - their number, what they said or typed, an SMS body - is scrubbed from
 * the exception message before it is stored.
 */
class WebhookFailureService
{
    public const RETENTION_DAYS = 30;
    public const RECENT_LIMIT = 25;
    public const MESSAGE_MAX_LENGTH = 1000;
    public const REDACTED = '[redacted]';

    private const TWILIO_MIDDLEWARE = 'twilio.signature';

    /** E.164, bare 10-15 digit runs, and (555) 123-4567 style numbers. */
    private const PHONE_PATTERNS = [
        '/\+\d{7,15}\b/',
        '/\b\d{10,15}\b/',
        '/\(?\b\d{3}\)?[\s.-]\d{3}[\s.-]\d{4}\b/',
    ];

    /** Request fields holding a Twilio identifier, never caller data. */
    private const SID_SUFFIX = 'Sid';

    /**
     * Store a failure if this request was a Twilio webhook that returned 5xx.
     * Never throws: recording must not change what Twilio receives.
     */
    public function record(Request $request, Response $response): void
    {
        if ($response->getStatusCode() < 500) {
            return;
        }

        try {
            if (!$this->isTwilioWebhook($request)) {
                return;
            }

            $exception = property_exists($response, 'exception') ? $response->exception : null;

            WebhookFailure::create([
                'created_at' => $this->utc(Carbon::now()),
                'method' => substr($request->getMethod(), 0, 8),
                'route' => Str::limit('/' . ltrim($request->path(), '/'), 188, ''),
                'status' => $response->getStatusCode(),
                'callsid' => $this->sidFor($request),
                'exception_class' => $exception instanceof Throwable ? Str::limit(get_class($exception), 252, '') : null,
                'message' => $exception instanceof Throwable
                    ? $this->scrub($exception->getMessage(), $request)
                    : null,
                'location' => $exception instanceof Throwable ? $this->locationOf($exception) : null,
            ]);

            $this->prune();
        } catch (Throwable $e) {
            Log::error('Could not record webhook failure: ' . $e->getMessage());
        }
    }

    /**
     * True when the request matched a route in the twilio.signature group.
     * Resolves the route itself when the failure happened in global middleware,
     * before the router had dispatched it.
     */
    public function isTwilioWebhook(Request $request): bool
    {
        $route = $request->route();
        if (!$route instanceof Route) {
            try {
                $route = RouteFacade::getRoutes()->match($request);
            } catch (Throwable) {
                return false;
            }
        }

        return in_array(self::TWILIO_MIDDLEWARE, $route->gatherMiddleware(), true);
    }

    /** Delete failures older than the retention window. */
    public function prune(): int
    {
        return WebhookFailure::where('created_at', '<', $this->utc(Carbon::now()->subDays(self::RETENTION_DAYS)))
            ->delete();
    }

    /**
     * Counts and the most recent failures, each with the events yap recorded
     * for that call.
     */
    public function summary(int $limit = self::RECENT_LIMIT): array
    {
        $this->prune();

        $now = Carbon::now();
        $recent = WebhookFailure::orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        $eventsBySid = $this->eventsFor($recent->pluck('callsid')->filter()->unique()->values()->all());

        return [
            'retention_days' => self::RETENTION_DAYS,
            'last_24_hours' => WebhookFailure::where('created_at', '>=', $this->utc($now->copy()->subDay()))->count(),
            'last_7_days' => WebhookFailure::where('created_at', '>=', $this->utc($now->copy()->subDays(7)))->count(),
            'last_failure_at' => $this->iso($recent->first()?->created_at),
            'failures' => $recent->map(fn (WebhookFailure $failure) => [
                'id' => $failure->id,
                'created_at' => $this->iso($failure->created_at),
                'method' => $failure->method,
                'route' => $failure->route,
                'status' => $failure->status,
                'callsid' => $failure->callsid,
                'exception_class' => $failure->exception_class,
                'message' => $failure->message,
                'location' => $failure->location,
                'events' => $failure->callsid ? ($eventsBySid[$failure->callsid] ?? []) : [],
            ])->all(),
        ];
    }

    /**
     * Remove anything the caller sent from an exception message: every value
     * in the request except Twilio's own identifiers, then anything left that
     * looks like a phone number.
     */
    public function scrub(string $message, Request $request): string
    {
        $values = [];
        foreach ($request->all() as $key => $value) {
            if (is_string($key) && str_ends_with($key, self::SID_SUFFIX)) {
                continue;
            }
            $value = is_array($value) ? $value : [$value];
            array_walk_recursive($value, function ($item) use (&$values) {
                if (is_scalar($item)) {
                    $item = trim((string) $item);
                    if (mb_strlen($item) >= 3) {
                        $values[] = $item;
                    }
                }
            });
        }

        // Longest first, so "+15551234567" is replaced before "5551234567".
        usort($values, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));
        foreach (array_unique($values) as $value) {
            // As sent, and as it would appear inside a URL in the message.
            foreach (array_unique([$value, urlencode($value), rawurlencode($value)]) as $form) {
                $message = str_ireplace($form, self::REDACTED, $message);
            }
        }

        $message = preg_replace(self::PHONE_PATTERNS, self::REDACTED, $message) ?? $message;

        return Str::limit($message, self::MESSAGE_MAX_LENGTH);
    }

    /** Rows are stored as UTC wall-clock, whatever PHP's default zone is. */
    private function utc(Carbon $time): string
    {
        return $time->copy()->setTimezone('UTC')->format('Y-m-d H:i:s');
    }

    private function iso(?string $utc): ?string
    {
        return $utc ? str_replace(' ', 'T', $utc) . 'Z' : null;
    }

    private function sidFor(Request $request): ?string
    {
        foreach (['CallSid', 'SmsSid', 'MessageSid'] as $key) {
            $sid = $request->input($key);
            if (is_string($sid) && $sid !== '') {
                return substr($sid, 0, 64);
            }
        }

        return null;
    }

    private function locationOf(Throwable $exception): string
    {
        $file = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $exception->getFile());

        return Str::limit(sprintf('%s:%d', $file, $exception->getLine()), 252, '');
    }

    /**
     * @param string[] $callSids
     * @return array<string, array<int, array{event_id:int, event_name:?string, event_time:?string, service_body_id:?int}>>
     */
    private function eventsFor(array $callSids): array
    {
        if (empty($callSids)) {
            return [];
        }

        $events = [];
        RecordEvent::whereIn('callsid', $callSids)
            ->orderBy('event_time')
            ->orderBy('id')
            ->get(['callsid', 'event_id', 'event_time', 'service_body_id'])
            ->each(function (RecordEvent $event) use (&$events) {
                $events[$event->callsid][] = [
                    'event_id' => (int) $event->event_id,
                    'event_name' => EventId::getEventById($event->event_id),
                    'event_time' => $this->iso($event->event_time),
                    'service_body_id' => $event->service_body_id !== null ? (int) $event->service_body_id : null,
                ];
            });

        return $events;
    }
}
