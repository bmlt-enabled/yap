<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $primaryKey = "id";
    public $timestamps = false;
    protected $fillable = ["name", "username", "password", "permissions", "is_admin", "created_on", "service_bodies"];

    public static function saveUser(
        string $name,
        string $username,
        string $password,
        array $permissions,
        array $service_bodies) {
        self::create([
            'name'=>$name,
            'username'=>$username,
            'password'=>hash('sha256', $password),
            'permissions'=>array_sum($permissions),
            'service_bodies'=>implode(",", $service_bodies),
            'is_admin'=>0
        ]);
    }

    public static function getUser($username)
    {
        return self::query()
            ->select(['id', 'name', 'username', 'is_admin', 'permissions', 'service_bodies', 'created_on'])
            ->where('username', $username)
            ->get();
    }

    public static function deleteUser($username)
    {
        return self::where(['username'=>$username])->delete();
    }
}
