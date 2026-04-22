<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_prospects', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('phone_number')->nullable();
            $table->string('telephone_number')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('email_address')->nullable();
            $table->text('location')->nullable();
            $table->enum('status', [
                'sent_email',
                'updated',
                'they_emailed',
                'hard_copy_needed',
                'no_response',
                'after_1_month',
                'email_back',
                'for_follow_up',
            ])->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_prospects');
    }
};