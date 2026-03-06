<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();

            // Link back to the original applicant record
            $table->foreignId('applicant_id')
                  ->nullable()
                  ->constrained('applicants')
                  ->nullOnDelete();

            // Branch & Position
            $table->foreignId('branch_id')
                  ->constrained('branches')
                  ->cascadeOnDelete();

            $table->foreignId('position_id')
                  ->nullable()
                  ->constrained('positions')
                  ->nullOnDelete();

            // ── Personal Information ──────────────────────────────────────
            $table->string('full_name');
            $table->date('date_of_birth')->nullable();
            $table->unsignedTinyInteger('age')->nullable();
            $table->enum('gender', ['Male', 'Female'])->nullable();
            $table->enum('civil_status', ['Single', 'Married', 'Widowed', 'Separated'])->nullable();

            // ── Contact Information ───────────────────────────────────────
            $table->string('contact_number', 20);
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_number', 20)->nullable();

            // ── Government IDs ────────────────────────────────────────────
            $table->string('sss', 50)->nullable();
            $table->string('pagibig', 50)->nullable();
            $table->string('philhealth', 50)->nullable();
            $table->string('tin', 50)->nullable();

            // ── Document Submission Status ────────────────────────────────
            // Each doc: 'submitted' | 'pending' | 'not_required'
            $table->enum('nbi_status', ['submitted', 'pending', 'not_required'])->default('pending');
            $table->date('nbi_expiry')->nullable();

            $table->enum('police_clearance_status', ['submitted', 'pending', 'not_required'])->default('pending');
            $table->date('police_clearance_expiry')->nullable();

            $table->enum('medcert_status', ['submitted', 'pending', 'not_required'])->default('pending');
            $table->date('medcert_expiry')->nullable();

            // ── 201 File / Requirements Status ───────────────────────────
            $table->enum('requirements_status', ['complete', 'incomplete', 'pending'])->default('pending');

            // ── Employment Details ────────────────────────────────────────
            $table->date('date_hired');
            $table->date('date_resigned')->nullable();
            $table->date('date_ended')->nullable();       // for endo/terminated/awol
            $table->decimal('daily_rate', 10, 2)->nullable();

            $table->enum('employment_status', [
                'resigned',
                'terminated',
                'endo',
                'awol',
            ])->nullable(); // NULL = actively employed

            // ── Source & Audit ────────────────────────────────────────────
            $table->string('source')->nullable();         // carried over from applicant
            $table->foreignId('created_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->text('remarks')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};