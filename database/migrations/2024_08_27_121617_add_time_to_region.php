<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('region', function (Blueprint $table) {
            $table->integer('time_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('region', function (Blueprint $table) {
            //
        });
    }
};
