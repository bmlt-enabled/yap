<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventStatus extends Model
{
    protected $table = 'event_status';

    protected $fillable = ['callsid', 'status', 'event_id'];

    use HasFactory;
}
