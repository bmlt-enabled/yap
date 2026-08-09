<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Retained for migration history only.
 *
 * personal_access_tokens.tokenable_id uses Laravel's default morphs() bigint, which
 * matches integer users.id. No column type change is required for Sanctum.
 */
class UpdateTokenableIdToUuid extends Migration
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
