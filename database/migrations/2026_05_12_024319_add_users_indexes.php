<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index('date_hired', 'users_date_hired_index');
            $table->index('employment_status', 'users_employment_status_index');
            $table->index('requirements_status', 'users_requirements_status_index');
            $table->index('is_active', 'users_is_active_index');
            $table->index(['is_active', 'date_hired'], 'users_active_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_date_hired_index');
            $table->dropIndex('users_employment_status_index');
            $table->dropIndex('users_requirements_status_index');
            $table->dropIndex('users_is_active_index');
            $table->dropIndex('users_active_date_index');
        });
    }
};