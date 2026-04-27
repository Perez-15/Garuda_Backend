<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('applicants', function (Blueprint $table) {
        $table->string('position')->nullable()->after('source');
    });
}

public function down()
{
    Schema::table('applicants', function (Blueprint $table) {
        $table->dropColumn('position');
    });
}
};
