<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_hr_actions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_id')
                  ->constrained('employees')
                  ->cascadeOnDelete();

            $table->enum('type', ['memo', 'ir', 'loa']);  // Memo | Incident Report | Leave of Absence

            $table->string('subject')->nullable();         // short label / title
            $table->text('description')->nullable();       // full details

            $table->date('action_date');                   // date of memo/IR/LOA

            // For LOA: optional date range
            $table->date('loa_start')->nullable();
            $table->date('loa_end')->nullable();

            $table->foreignId('created_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_hr_actions');
    }
};