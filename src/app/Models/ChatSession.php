<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class ChatSession extends Model
{
    use HasUuids;

    protected $table = 'chat_sessions';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'client_id',
        'volunteer_phone',
        'service_body_id',
        'status',
        'messages',
        'location',
        'latitude',
        'longitude',
        'last_activity_at',
    ];

    protected $casts = [
        'messages' => 'array',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'last_activity_at' => 'datetime',
        'service_body_id' => 'integer',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_ACTIVE = 'active';
    const STATUS_CLOSED = 'closed';

    /**
     * Add a message to the session
     */
    public function addMessage(string $content, string $sender, ?string $senderName = null): void
    {
        $messages = $this->messages ?? [];
        $messages[] = [
            'id' => uniqid(),
            'content' => $content,
            'sender' => $sender, // 'user', 'volunteer', 'system'
            'sender_name' => $senderName,
            'timestamp' => Carbon::now()->toIso8601String(),
        ];
        $this->messages = $messages;
        $this->last_activity_at = Carbon::now();
        $this->save();
    }

    /**
     * Get messages since a given timestamp
     */
    public function getMessagesSince(?string $since = null): array
    {
        $messages = $this->messages ?? [];

        if (!$since) {
            return $messages;
        }

        $sinceTime = Carbon::parse($since);
        return array_values(array_filter($messages, function ($msg) use ($sinceTime) {
            return Carbon::parse($msg['timestamp'])->gt($sinceTime);
        }));
    }

    /**
     * Check if session has timed out
     */
    public function hasTimedOut(int $timeoutMinutes = 30): bool
    {
        if (!$this->last_activity_at) {
            return false;
        }
        return $this->last_activity_at->diffInMinutes(Carbon::now()) >= $timeoutMinutes;
    }

    /**
     * Scope for active sessions
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for pending sessions
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Find active session by volunteer phone
     */
    public static function findActiveByVolunteerPhone(string $phone): ?self
    {
        return self::where('volunteer_phone', $phone)
            ->where('status', self::STATUS_ACTIVE)
            ->first();
    }

    /**
     * Find session by client ID
     */
    public static function findByClientId(string $clientId): ?self
    {
        return self::where('client_id', $clientId)
            ->whereIn('status', [self::STATUS_PENDING, self::STATUS_ACTIVE])
            ->orderBy('created_at', 'desc')
            ->first();
    }
}
