<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('profile_photo')->nullable()->after('name');
            $table->text('address')->nullable()->after('contact_number');
            $table->date('date_of_birth')->nullable()->after('date_hired');
            $table->string('gender')->nullable()->after('date_of_birth');
            $table->string('civil_status')->nullable()->after('gender');
            $table->string('emergency_contact_name')->nullable()->after('civil_status');
            $table->string('emergency_contact_number')->nullable()->after('emergency_contact_name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'profile_photo',
                'address',
                'date_of_birth',
                'gender',
                'civil_status',
                'emergency_contact_name',
                'emergency_contact_number',
            ]);
        });
    }
};