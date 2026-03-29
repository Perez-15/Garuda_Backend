<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up(): void
{
    // Step 1: expand — allow both values simultaneously
    DB::statement("ALTER TABLE applicants MODIFY COLUMN status ENUM('active','pooling','hired','backout','back_out')");

    // Step 2: convert all existing 'backout' rows to 'back_out'
    DB::statement("UPDATE applicants SET status = 'back_out' WHERE status = 'backout'");

    // Step 3: shrink — remove the old value
    DB::statement("ALTER TABLE applicants MODIFY COLUMN status ENUM('active','pooling','hired','back_out')");
}

public function down(): void
{
    DB::statement("ALTER TABLE applicants MODIFY COLUMN status ENUM('active','pooling','hired','backout','back_out')");
    DB::statement("UPDATE applicants SET status = 'backout' WHERE status = 'back_out'");
    DB::statement("ALTER TABLE applicants MODIFY COLUMN status ENUM('active','pooling','hired','backout')");

}
};
