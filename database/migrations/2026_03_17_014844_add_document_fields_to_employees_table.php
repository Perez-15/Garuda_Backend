<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ── Fix employment_status — add 'hired' to the existing enum ──────────
        // Current:  enum('resigned','terminated','endo','awol')
        // After:    enum('hired','resigned','terminated','endo','awol')
        DB::statement("
            ALTER TABLE employees
            MODIFY COLUMN employment_status
            ENUM('hired','resigned','terminated','endo','awol')
            DEFAULT 'hired'
        ");

        // ── Add the 9 missing document status columns ─────────────────────────
        Schema::table('employees', function (Blueprint $table) {

            // PSA / Birth Certificate
            $table->enum('psa_status', ['submitted', 'pending', 'not_required'])
                  ->default('pending')
                  ->after('requirements_status');

            // SSS Document (physical copy — separate from the sss number field)
            $table->enum('sss_document_status', ['submitted', 'pending', 'not_required'])
                  ->default('pending')
                  ->after('psa_status');

            // PhilHealth Document
            $table->enum('philhealth_document_status', ['submitted', 'pending', 'not_required'])
                  ->default('pending')
                  ->after('sss_document_status');

            // Pag-ibig Document
            $table->enum('pagibig_document_status', ['submitted', 'pending', 'not_required'])
                  ->default('pending')
                  ->after('philhealth_document_status');

            // TIN Document
            $table->enum('tin_document_status', ['submitted', 'pending', 'not_required'])
                  ->default('pending')
                  ->after('pagibig_document_status');

            // Certificate of Employment (COE)
            $table->enum('coe_status', ['submitted', 'pending', 'not_required'])
                  ->default('pending')
                  ->after('medcert_expiry');

            // TOR / Diploma
            $table->enum('tor_diploma_status', ['submitted', 'pending', 'not_required'])
                  ->default('pending')
                  ->after('coe_status');

            // Photocopy of Valid ID
            $table->enum('valid_id_status', ['submitted', 'pending', 'not_required'])
                  ->default('pending')
                  ->after('tor_diploma_status');

            // 2pcs 1x1 Picture
            $table->enum('picture_1x1_status', ['submitted', 'pending', 'not_required'])
                  ->default('pending')
                  ->after('valid_id_status');
        });
    }

    public function down(): void
    {
        // Remove the 9 new columns
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'psa_status',
                'sss_document_status',
                'philhealth_document_status',
                'pagibig_document_status',
                'tin_document_status',
                'coe_status',
                'tor_diploma_status',
                'valid_id_status',
                'picture_1x1_status',
            ]);
        });

        // Revert employment_status back to original (without 'hired')
        DB::statement("
            ALTER TABLE employees
            MODIFY COLUMN employment_status
            ENUM('resigned','terminated','endo','awol')
            DEFAULT NULL
        ");
    }
};