<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('package_service_bookings', function (Blueprint $table) {
            $table->integer('car_id')->nullable();
            $table->integer('address_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('package_service_bookings', function (Blueprint $table) {
            //
        });
    }
};
