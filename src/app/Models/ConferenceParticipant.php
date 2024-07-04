<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConferenceParticipant extends Model
{
    protected $primaryKey = "id";
    protected $table = "conference_participants";
    public $timestamps = false;
    protected $fillable = ["timestamp", "conferencesid", "callsid", "friendlyname", "role"];

    public static function generate(
        string $conferenceSid,
        string $callSid,
        string $friendlyName,
        int $role
    ): void {
        self::create([
            "conferencesid"=>$conferenceSid,
            "callsid"=>$callSid,
            "friendlyname"=>$friendlyName,
            "role"=>$role
        ]);
    }
}
