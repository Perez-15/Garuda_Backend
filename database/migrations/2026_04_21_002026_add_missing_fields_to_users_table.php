<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('age')->nullable()->after('date_of_birth');
            $table->date('date_ended')->nullable()->after('date_hired');
            $table->date('date_resigned')->nullable()->after('date_ended');
            $table->decimal('daily_rate', 10, 2)->nullable()->after('date_resigned');
            $table->string('employment_status', 50)->nullable()->after('daily_rate');
            $table->string('source', 100)->nullable()->after('employment_status');
            $table->text('remarks')->nullable()->after('source');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'age', 'date_ended', 'date_resigned',
                'daily_rate', 'employment_status', 'source', 'remarks',
            ]);
        });
    }
};