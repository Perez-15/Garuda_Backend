<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applicant_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('activity_type'); // step_change, status_update, note_added, etc.
            $table->text('description');
            $table->json('metadata')->nullable(); // Store additional data
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applicant_activities');
    }
};