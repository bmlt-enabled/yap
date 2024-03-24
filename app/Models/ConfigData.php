<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfigData extends Model
{
    protected $primaryKey = "id";
    protected $table = "config";
    public $timestamps = false;
    protected $fillable = ["service_body_id", "data", "data_type", "parent_id", "status"];
}
