<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up(): void
{
    Schema::table('branches', function (Blueprint $table) {
        // Remove old foreign key columns if they exist
        if (Schema::hasColumn('branches', 'rm_id')) {
            $table->dropForeign(['rm_id']);
            $table->dropColumn('rm_id');
        }
        if (Schema::hasColumn('branches', 'arm_id')) {
            $table->dropForeign(['arm_id']);
            $table->dropColumn('arm_id');
        }
        // Add name columns instead
        $table->string('rm_name')->nullable();
        $table->string('arm_name')->nullable();
    });
}

public function down(): void
{
    Schema::table('branches', function (Blueprint $table) {
        $table->dropColumn(['rm_name', 'arm_name']);
    });
}
};