<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('package_services', function (Blueprint $table) {
            $table->string('service_type_data')->nullable();
            $table->string('count')->nullable();
            $table->string('usage_times')->nullable();
            $table->string('duration_of_use')->nullable();
            $table->string('price')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('package_services', function (Blueprint $table) {
            //
        });
    }
};
