<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('service_pacakge_service_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->integer('service_id')->nullable();
            $table->string('service_type_data')->nullable();
            $table->string('count')->nullable();
            $table->string('usage_times')->nullable();
            $table->string('duration_of_use')->nullable();
            $table->string('price')->nullable();
            $table->integer('subscription_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_pacakge_service_subscriptions');
    }
};
