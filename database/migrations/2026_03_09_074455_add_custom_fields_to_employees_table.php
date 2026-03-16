<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Add custom_fields JSON column to employees ─────────────────────
        Schema::table('employees', function (Blueprint $table) {
            $table->json('custom_fields')->nullable()->after('remarks');
        });

        // ── 2. New table: stores the custom column definitions per page ────────
        //    page:      'employed' | 'applicants' | 'in_process' | 'hired'
        //    field_key: snake_case unique key e.g. 'allowance', 'shift_schedule'
        //    label:     display name shown in the table header
        //    type:      'text' | 'number' | 'date' | 'select'
        //    options:   JSON array of choices (only used when type = 'select')
        //    order:     display order in the table
        Schema::create('custom_columns', function (Blueprint $table) {
            $table->id();
            $table->string('page');                        // which page this column belongs to
            $table->string('field_key');                   // snake_case key used in custom_fields JSON
            $table->string('label');                       // display name
            $table->string('type')->default('text');       // text | number | date | select
            $table->json('options')->nullable();           // for select type
            $table->unsignedInteger('order')->default(0); // sort order
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // One field_key per page only
            $table->unique(['page', 'field_key']);
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('custom_fields');
        });
        Schema::dropIfExists('custom_columns');
    }
};