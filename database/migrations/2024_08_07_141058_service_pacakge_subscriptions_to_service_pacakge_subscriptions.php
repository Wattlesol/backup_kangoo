<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('service_pacakge_subscriptions', function (Blueprint $table) {
            $table->string('car_number')->nullable();;
        });
    }

    public function down(): void
    {
        Schema::table('service_pacakge_subscriptions', function (Blueprint $table) {
            //
        });
    }
};
