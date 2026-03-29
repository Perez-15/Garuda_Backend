<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_hr_actions', function (Blueprint $table) {
            $table->string('file_name')->nullable()->after('loa_end');   // original filename for display
            $table->string('file_path')->nullable()->after('file_name'); // storage path: hr_actions/filename.pdf
            $table->unsignedBigInteger('file_size')->nullable()->after('file_path');
        });
    }

    public function down(): void
    {
        Schema::table('employee_hr_actions', function (Blueprint $table) {
            $table->dropColumn(['file_name', 'file_path', 'file_size']);
        });
    }
};