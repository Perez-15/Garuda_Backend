<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->index('branch_id', 'employees_branch_id_index');
            $table->index('created_by', 'employees_created_by_index');
            $table->index('date_hired', 'employees_date_hired_index');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['branch_id']);
            $table->dropIndex(['created_by']);
            $table->dropIndex(['date_hired']);
        });
    }
};