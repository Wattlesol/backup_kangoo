<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('package_complaints', function (Blueprint $table) {
            $table->string('file')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('package_complaints', function (Blueprint $table) {
            //
        });
    }
};
