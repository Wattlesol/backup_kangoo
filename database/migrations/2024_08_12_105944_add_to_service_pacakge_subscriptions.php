<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('service_pacakge_subscriptions', function (Blueprint $table) {
            $table->integer('city_id')->nullable();
            $table->integer('region_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('service_pacakge_subscriptions', function (Blueprint $table) {
            //
        });
    }
};
