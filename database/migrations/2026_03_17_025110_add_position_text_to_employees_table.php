<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Add plain text position column after position_id
            $table->string('position')->nullable()->after('position_id');
        });

        // Migrate existing position_id data into the new text column
        DB::statement("
            UPDATE employees e
            LEFT JOIN positions p ON e.position_id = p.id
            SET e.position = p.name
            WHERE e.position_id IS NOT NULL
        ");
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('position');
        });
    }
};