<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_postings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('location');
            $table->string('category')->nullable();
            $table->string('salary')->default('TBD');
            $table->enum('type', ['Full-time', 'Part-time', 'Contract'])->default('Full-time');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('posted_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_postings');
    }
};
