<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('subscription_cars', function (Blueprint $table) {
            $table->id();
            $table->text('car_number')->nullable();
            $table->text('car_year')->nullable();
            $table->text('car_model')->nullable();
            $table->integer('subscription_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_cars');
    }
};
