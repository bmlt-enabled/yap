<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CacheRecordsConferenceParticipants extends Model
{
    protected $table = "cache_records_conference_participants";
    public $timestamps = false;
    protected $fillable = ["parent_callsid", "callsid", "guid", "service_body_id"];
}
