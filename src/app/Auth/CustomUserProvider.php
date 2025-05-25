<?php

namespace App\Auth;

use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use App\Models\User;
use App\Services\AuthenticationService;

class CustomUserProvider implements UserProvider
{
    protected AuthenticationService $authService;

    public function __construct(AuthenticationService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Retrieve a user by their unique identifier (e.g., ID).
     */
    public function retrieveById($identifier)
    {
        return User::find($identifier);
    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     */
    public function retrieveByToken($identifier, $token)
    {
        $user = $this->retrieveById($identifier);

        if ($user && $user->getRememberToken() === $token) {
            return $user;
        }

        return null;
    }

    /**
     * Update the "remember me" token for the user.
     */
    public function updateRememberToken(Authenticatable $user, $token)
    {
        $user->setRememberToken($token);
        $user->save();
    }

    /**
     * Retrieve a user by their credentials.
     */
    public function retrieveByCredentials(array $credentials)
    {
        $username = $credentials['username'] ?? null;
        $password = $credentials['password'] ?? null;

        // Use your custom AuthenticationService logic to authenticate the user
        $user = $this->authService->authenticateApi($username, $password);

        return $user;
    }

    /**
     * Validate a user against the given credentials.
     */
    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        $plainPassword = $credentials['password'];

        $hashedPassword = hash('sha256', $plainPassword);

        // Compare with the stored password
        return hash_equals($user->getAuthPassword(), $hashedPassword);
    }

    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false) {

    }
}
