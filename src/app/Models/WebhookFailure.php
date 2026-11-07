<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A 5xx returned to Twilio by one of the twilio.signature webhooks
 * (bmlt-enabled/yap#1686). Written by WebhookFailureService.
 */
class WebhookFailure extends Model
{
    protected $table = 'webhook_failures';
    public $timestamps = false;
    protected $fillable = [
        'created_at',
        'method',
        'route',
        'status',
        'callsid',
        'exception_class',
        'message',
        'location',
    ];
    protected $casts = [
        'status' => 'integer',
    ];
}
