<?php

namespace App\Http\Middleware;

use App\Repositories\ReportsRepository;
use App\Services\SessionService;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CallSession
{
    private SessionService $session;
    private ReportsRepository $reportsRepository;

    public function __construct(SessionService $session, ReportsRepository $reportsRepository)
    {
        $this->session = $session;
        $this->reportsRepository = $reportsRepository;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(Request): (Response|RedirectResponse) $next
     * @return Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        @session_start();

        // Establish the pin for the callsid in the database if not set yet.
        if ($request->has("CallSid")) {
            $this->reportsRepository->insertSession($request->get("CallSid"));
        }

        // Set the configuration for for service bodies overrides
        if (!isset($_SESSION['override_service_body_id']) && !isset($_SESSION["override_service_body_config_id"])) {
            $service_body_id = 0;
            if ($request->has("service_body_id") || $request->has("override_service_body_id")) {
                $service_body_id = $request->has("service_body_id") ? $request->get("service_body_id") : $request->get("override_service_body_id");
            } elseif ($request->has("override_service_body_config_id")) {
                $service_body_id = $request->get("override_service_body_config_id");
            }

            $this->session->setConfigForService($service_body_id);
        }

        // Set the call state
        if (!isset($_SESSION['call_state'])) {
            $_SESSION['call_state'] = "STARTED";
        }

        // Set the initial webhook for the session
        if (!isset($_SESSION['initial_webhook'])) {
            $webhook_array = explode("/", $_SERVER['REQUEST_URI']);
            $_SESSION['initial_webhook'] = str_replace("&", "&amp;", $webhook_array[count($webhook_array) - 1]);
        }

        // Override specific values for the session
        foreach ($request->all() as $key => $value) {
            if (str_contains($key, "override_")) {
                $_SESSION[$key] = $value;
            }
        }

        return $next($request);
    }
}
