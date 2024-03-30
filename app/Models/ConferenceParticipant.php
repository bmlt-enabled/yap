<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConferenceParticipant extends Model
{
    protected $primaryKey = "id";
    protected $table = "conference_participants";
    public $timestamps = false;
    protected $fillable = ["timestamp", "conferencesid", "callsid", "friendlyname", "role"];
}
