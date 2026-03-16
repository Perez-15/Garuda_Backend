<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('custom_columns', function (Blueprint $table) {
            // Groups fields into sections e.g. "Personal Information", "Employment Details"
            $table->string('section')->default('General')->after('page');

            // Controls which employee page type sees this field
            // 'ext'  = External employees only
            // 'int'  = Internal employees only
            // 'both' = Visible on both pages
            $table->string('scope')->default('both')->after('order');

            // Whether this field must be filled in before saving
            $table->boolean('required')->default(false)->after('scope');

            // true  = maps to a real fixed column on the employees table (e.g. sss, pagibig)
            // false = maps to a key inside the custom_fields JSON column
            $table->boolean('is_fixed')->default(false)->after('required');
        });
    }

    public function down(): void
    {
        Schema::table('custom_columns', function (Blueprint $table) {
            $table->dropColumn(['section', 'scope', 'required', 'is_fixed']);
        });
    }
};