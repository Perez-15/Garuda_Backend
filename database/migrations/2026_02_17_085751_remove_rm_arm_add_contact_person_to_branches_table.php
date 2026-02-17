<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            // Remove rm and arm columns if they exist
            if (Schema::hasColumn('branches', 'rm_id')) {
                $table->dropForeign(['rm_id']);
                $table->dropColumn('rm_id');
            }
            if (Schema::hasColumn('branches', 'arm_id')) {
                $table->dropForeign(['arm_id']);
                $table->dropColumn('arm_id');
            }
            if (Schema::hasColumn('branches', 'rm_name')) {
                $table->dropColumn('rm_name');
            }
            if (Schema::hasColumn('branches', 'arm_name')) {
                $table->dropColumn('arm_name');
            }

            // Add contact person
            if (!Schema::hasColumn('branches', 'contact_person')) {
                $table->string('contact_person')->nullable()->after('location');
            }
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn('contact_person');
            $table->string('rm_name')->nullable();
            $table->string('arm_name')->nullable();
        });
    }
};