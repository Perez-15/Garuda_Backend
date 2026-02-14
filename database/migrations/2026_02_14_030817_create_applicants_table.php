<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applicants', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('email')->unique();
            $table->string('phone');
            $table->string('source'); // WordPress, Gmail, Facebook, etc.
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->foreignId('workflow_id')->constrained()->onDelete('cascade');
            $table->foreignId('current_step_id')->nullable()->constrained('workflow_steps')->onDelete('set null');
            $table->string('resume_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('applied_at')->useCurrent();
            $table->enum('status', ['active', 'withdrawn', 'hired', 'rejected'])->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applicants');
    }
};