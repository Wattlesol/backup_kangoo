<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('package_complaints', function (Blueprint $table) {
            $table->id();
            $table->string('user_id');
            $table->string('subscription_id');
            $table->string('service_id');
            $table->string('booking_id');
            $table->string('complaint_type');
            $table->longText('complaint_details');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_complaints');
    }
};
