<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('contact_number')->nullable()->after('email');
            $table->string('department')->nullable()->after('contact_number'); // hr, talent_acquisition, accounting, marketing
            $table->date('date_hired')->nullable()->after('department');

            // Requirements
            $table->enum('nbi_status',              ['submitted', 'pending', 'not_required'])->nullable()->after('date_hired');
            $table->enum('medcert_status',          ['submitted', 'pending', 'not_required'])->nullable()->after('nbi_status');
            $table->enum('police_clearance_status', ['submitted', 'pending', 'not_required'])->nullable()->after('medcert_status');
            $table->enum('contract_status',         ['submitted', 'pending', 'not_required'])->nullable()->after('police_clearance_status');
            $table->string('sss',        50)->nullable()->after('contract_status');
            $table->string('pagibig',    50)->nullable()->after('sss');
            $table->string('philhealth', 50)->nullable()->after('pagibig');
            $table->string('tin',        50)->nullable()->after('philhealth');

            // Overall requirements status
            $table->enum('requirements_status', ['complete', 'incomplete', 'pending'])->nullable()->after('tin');

            // Custom fields JSON
            $table->json('custom_fields')->nullable()->after('requirements_status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'contact_number',
                'department',
                'date_hired',
                'nbi_status',
                'medcert_status',
                'police_clearance_status',
                'contract_status',
                'sss',
                'pagibig',
                'philhealth',
                'tin',
                'requirements_status',
                'custom_fields',
            ]);
        });
    }
};