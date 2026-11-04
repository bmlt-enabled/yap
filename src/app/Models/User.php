<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;
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

    /**
     * Hash a password for the users table. Every new or changed password goes
     * through here, so it gets a salted hash (bcrypt by default).
     */
    public static function hashPassword(string $password): string
    {
        return Hash::make($password);
    }

    /**
     * Before #1675, passwords were stored as unsalted SHA-256: 64 hex digits,
     * the format MySQL's SHA2(<password>, 256) writes. Those rows still log in
     * and are re-hashed on their next successful login.
     */
    public static function isLegacyPasswordHash(string $stored): bool
    {
        return preg_match('/\A[0-9a-f]{64}\z/i', $stored) === 1;
    }

    /**
     * Does $password match this row's stored hash? A row with no stored
     * password (every BMLT row) never matches, whatever is typed.
     */
    public function checkPassword(string $password): bool
    {
        $stored = (string) $this->password;
        if ($stored === '') {
            return false;
        }

        if (self::isLegacyPasswordHash($stored)) {
            return hash_equals(strtolower($stored), hash('sha256', $password));
        }

        try {
            return Hash::check($password, $stored);
        } catch (\RuntimeException) {
            // Not a hash the configured driver understands.
            return false;
        }
    }

    /**
     * True when the stored hash is the legacy SHA-256 format, or a hash the
     * configured driver would now write differently (e.g. a higher cost).
     */
    public function passwordNeedsRehash(): bool
    {
        $stored = (string) $this->password;
        return self::isLegacyPasswordHash($stored) || Hash::needsRehash($stored);
    }

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
            'password'=>self::hashPassword($password),
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
            $user->password = self::hashPassword($password);
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
            $user->password = self::hashPassword($password);
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
