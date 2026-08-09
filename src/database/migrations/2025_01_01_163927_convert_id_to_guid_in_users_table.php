<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Retained for migration history only.
 *
 * Early 5.0.0 development converted users.id to UUIDs. That migration was removed:
 * Sanctum works with integer primary keys, Yap keys users by username, and no other
 * table references users.id. Keeping integer ids avoids a destructive upgrade step.
 */
class ConvertIdToGuidInUsersTable extends Migration
{
    public function up(): void
    {
        // Intentionally empty.
    }

    public function down(): void
    {
        // Intentionally empty.
    }
}
