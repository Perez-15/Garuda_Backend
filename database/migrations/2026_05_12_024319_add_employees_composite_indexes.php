<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->index(['branch_id', 'employment_status'], 'employees_branch_status_index');
            $table->index(['created_by', 'date_hired'], 'employees_created_by_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex('employees_branch_status_index');
            $table->dropIndex('employees_created_by_date_index');
        });
    }
};