<?php

namespace App\Models;

use App\Constants\AlertId;
use App\Constants\DataType;
use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    protected $primaryKey = "id";
    protected $table = "alerts";
    public $timestamps = false;
    protected $fillable = ["alert_id", "payload", "status"];

    public static function createMisconfiguredPhoneNumberAlert(
        string $phoneNumber
    ) : void {
        self::create([
            "alert_id"=>AlertId::STATUS_CALLBACK_MISSING,
            "payload"=>$phoneNumber,
            "timestamp"=>gmdate("Y-m-d H:i:s")
        ]);
    }
}
