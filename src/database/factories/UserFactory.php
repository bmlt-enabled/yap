<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /** Hashed once per process; bcrypt is deliberately slow. */
    protected static ?string $password = null;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => fake()->name(),
            'username' => fake()->unique()->userName(), // Generate unique usernames
            'password' => static::$password ??= Hash::make('password'),
            'permissions' => 0,
            'is_admin' => 0, // Or null, depending on your requirements
            'created_on' => now(),
            'service_bodies' => null, // Or generate data as needed for this field
        ];
    }

    public function admin()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_admin' => 1,
            ];
        });
    }

}
