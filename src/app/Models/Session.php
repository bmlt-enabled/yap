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

    public static function getPin($callSid): int
    {
        $pinData = self::query()->select(['pin'])
            ->where('callsid', $callSid)
            ->orderBy('timestamp')
            ->limit(1)
            ->first(['pin']);

        return (int)$pinData?->pin;
    }
}
