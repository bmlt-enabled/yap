<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cache extends Model
{
    protected $primaryKey = "id";
    protected $table = "cache";
    public $timestamps = false;
    protected $fillable = ["key", "value", "expiry"];
}
