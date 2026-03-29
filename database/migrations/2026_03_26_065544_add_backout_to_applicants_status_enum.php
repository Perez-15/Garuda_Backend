<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
public function up(): void
{
    DB::statement("ALTER TABLE applicants MODIFY COLUMN status ENUM('active', 'pooling', 'hired', 'backout') NOT NULL");
}

public function down(): void
{
    DB::statement("ALTER TABLE applicants MODIFY COLUMN status ENUM('active', 'pooling', 'hired') NOT NULL");
}
};