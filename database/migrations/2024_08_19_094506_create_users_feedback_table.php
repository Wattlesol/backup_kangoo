<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users_feedback', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->nullable();
            $table->integer('booking_id')->nullable();
            $table->integer('subscription_id')->nullable();
            $table->integer('rate')->nullable();
            $table->longText('feedback')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users_feedback');
    }
};
