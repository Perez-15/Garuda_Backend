<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up()
{
    // First fix any NULL or invalid status values
    DB::statement("UPDATE applicants SET status = 'active' WHERE status IS NULL OR status NOT IN ('active','pooling','hired','backout')");
    
    // Then alter the column
    DB::statement("ALTER TABLE applicants MODIFY COLUMN status ENUM('active','pooling','hired','backout') NOT NULL DEFAULT 'active'");
}

public function down()
{
    DB::statement("ALTER TABLE applicants MODIFY COLUMN status ENUM('active','pooling','hired','backout') NOT NULL");
}
};
