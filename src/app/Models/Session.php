<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
    protected $table = "sessions";
    public $timestamps = false;
    protected $fillable = ["callsid", "pin"];

    public static function generate($callsid, $pin) : void
    {
        self::create([
            "callsid"=>$callsid,
            "pin"=>$pin
        ]);
    }
}
