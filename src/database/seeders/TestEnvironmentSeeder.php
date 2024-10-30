<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TestEnvironmentSeeder extends Seeder
{
    public function run() : void
    {
        if (getenv("ENVIRONMENT") === "test") {
            DB::statement("
                INSERT INTO users (name, username, password, permissions, is_admin)
                VALUES (?, ?, SHA2(?, 256), 0, 1);
            ", ['admin', 'admin', 'admin']);
        }
    }
}
