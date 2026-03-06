<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Expand enum to include ALL values so no data truncation occurs
        DB::statement("
            ALTER TABLE applicants
            MODIFY COLUMN `status` ENUM('active', 'withdrawn', 'hired', 'rejected', 'pooling')
            NOT NULL DEFAULT 'active'
        ");

        // Step 2: Now safely convert rejected/withdrawn → pooling
        DB::table('applicants')
            ->whereIn('status', ['rejected', 'withdrawn'])
            ->update(['status' => 'pooling']);

        // Step 3: Remove old values, leaving only the new clean enum
        DB::statement("
            ALTER TABLE applicants
            MODIFY COLUMN `status` ENUM('active', 'hired', 'pooling')
            NOT NULL DEFAULT 'active'
        ");
    }

    public function down(): void
    {
        // Step 1: Temporarily allow all values so data conversion won't fail
        DB::statement("
            ALTER TABLE applicants
            MODIFY COLUMN `status` ENUM('active', 'hired', 'pooling', 'rejected', 'withdrawn')
            NOT NULL DEFAULT 'active'
        ");

        // Step 2: Convert pooling back to rejected as closest equivalent
        DB::table('applicants')
            ->where('status', 'pooling')
            ->update(['status' => 'rejected']);

        // Step 3: Restore original enum
        DB::statement("
            ALTER TABLE applicants
            MODIFY COLUMN `status` ENUM('active', 'withdrawn', 'hired', 'rejected')
            NOT NULL DEFAULT 'active'
        ");
    }
};