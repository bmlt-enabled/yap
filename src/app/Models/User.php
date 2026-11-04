<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /** A yap account whose password is checked against the users table. */
    public const AUTH_SOURCE_LOCAL = 'local';

    /**
     * A BMLT root server account. Its row exists only so the admin API has a
     * user to issue a token for: it has no usable password, gets its service
     * bodies from BMLT on every login, and is never a yap admin.
     */
    public const AUTH_SOURCE_BMLT = 'bmlt';

    protected $primaryKey = "id";
    public $timestamps = false;
    protected $fillable = ["name", "username", "password", "permissions", "is_admin", "created_on", "service_bodies"];
    protected $hidden = ["password", "remember_token"];
    protected $attributes = ["auth_source" => self::AUTH_SOURCE_LOCAL];

    public function isBmltUser(): bool
    {
        return $this->auth_source === self::AUTH_SOURCE_BMLT;
    }

    /**
     * Create or refresh the row for a BMLT login. Everything except the
     * display name is overwritten, so BMLT stays the source of truth.
     */
    public static function syncBmltUser(string $username, array $serviceBodyIds): self
    {
        $user = self::firstOrNew(['username' => $username]);
        if (!$user->exists) {
            $user->name = $username;
        }
        $user->forceFill([
            'auth_source' => self::AUTH_SOURCE_BMLT,
            'password' => '',
            'is_admin' => 0,
            'permissions' => 0,
            'service_bodies' => implode(',', $serviceBodyIds),
        ])->save();

        return $user;
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
        // A BMLT user's password lives in BMLT; their row never gets one.
        if (strlen($password) > 0 && !$user->isBmltUser()) {
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

        // A BMLT user's password lives in BMLT; their row never gets one.
        if (strlen($password) > 0 && !$user->isBmltUser()) {
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
