<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $primaryKey = "id";
    public $timestamps = false;
    protected $fillable = ["name", "username", "password", "permissions", "is_admin", "created_on", "service_bodies"];
}
