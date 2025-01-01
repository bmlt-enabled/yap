<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $primaryKey = "id";
    public $incrementing = false; // Disable auto-increment
    protected $keyType = 'string';
    public $timestamps = false;
    protected $fillable = ["name", "username", "password", "permissions", "is_admin", "created_on", "service_bodies"];

    protected static function boot()
    {
        parent::boot();

        // Automatically generate UUID for the `id` field on creating a new user
        static::creating(function ($user) {
            if (empty($user->id)) {
                $user->id = Str::uuid()->toString();
            }
        });
    }

    public static function saveUser(
        string $name,
        string $username,
        string $password,
        array $permissions,
        array $service_bodies
    ) {
        return self::create([
            'name'=>$name,
            'username'=>$username,
            'password'=>hash('sha256', $password),
            'permissions'=>array_sum($permissions),
            'service_bodies'=>implode(",", $service_bodies),
            'is_admin'=>0
        ]);
    }

    public static function editUserForSelf(
        string $name,
        string $username,
        string $password
    ) {
        $user = self::where('username', $username)->first();
        $user->name = $name;
        if (strlen($password) > 0) {
            $user->password = hash('sha256', $password);
        }

        $user->save();
        return $user;
    }

    public static function editUserForAdmin(
        string $name,
        string $username,
        string $password,
        array $permissions,
        array $service_bodies
    ) {
        $user = self::where('username', $username)->first();
        $user->name = $name;
        $user->permissions = array_sum($permissions);
        $user->username = $username;
        $user->service_bodies = implode(",", $service_bodies);

        if (strlen($password) > 0) {
            $user->password = hash('sha256', $password);
        }

        $user->save();
        return $user;
    }

    public static function getUser($username)
    {
        return self::query()
            ->select(['name', 'username', 'is_admin', 'permissions', 'service_bodies', 'created_on'])
            ->where('username', $username)
            ->get();
    }

    public static function getUsers()
    {
        return self::query()
            ->select(['name', 'username', 'is_admin', 'permissions', 'service_bodies', 'created_on'])
            ->get();
    }

    public static function deleteUser($username)
    {
        return self::where(['username'=>$username])->delete();
    }
}
