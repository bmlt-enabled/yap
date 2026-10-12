<?php

namespace App\Http\Controllers;

use App\Services\SettingsService;
use App\Utilities\MessagingChannel;
use Illuminate\Http\Response;

class WhatsAppHelplineController extends Controller
{
    public function __construct(protected SettingsService $settings)
    {
    }

    public function index(): Response
    {
        $number = $this->settings->get('whatsapp_number');
        $configured = is_string($number) && strlen(trim($number)) > 0;
        $prefill = $this->settings->get('whatsapp_prefill');
        $keyword = $this->settings->get('sms_helpline_keyword') ?: 'talk';

        return response()->view('whatsappHelpline', [
            'title' => $this->settings->get('title') ?: 'Helpline',
            'configured' => $configured,
            'displayNumber' => $configured ? MessagingChannel::e164($number) : '',
            'clickToChatUrl' => $configured
                ? MessagingChannel::clickToChatUrl($number, is_string($prefill) && $prefill !== '' ? $prefill : null)
                : '',
            'keyword' => $keyword,
            'webrtcEnabled' => (bool) $this->settings->get('webrtc_enabled'),
        ]);
    }
}
