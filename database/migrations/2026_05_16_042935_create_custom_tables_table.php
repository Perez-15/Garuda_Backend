<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up(): void
{
    Schema::create('custom_tables', function (Blueprint $table) {
        $table->id();
        $table->string('page')->unique();
        $table->string('label');
        $table->enum('scope', ['ext', 'int', 'both'])->default('both');
        $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('custom_tables');
}
};
