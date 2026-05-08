<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->whereNull('employment_status')
            ->update(['employment_status' => 'active']);
    }

    public function down(): void
    {
        // intentionally left blank — no safe way to reverse a data backfill
    }
};