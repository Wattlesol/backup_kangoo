<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('provider_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('slug')->unique();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->unsignedBigInteger('country_id')->nullable();
            $table->unsignedBigInteger('state_id')->nullable();
            $table->unsignedBigInteger('city_id')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'suspended'])->default('pending');
            $table->boolean('is_active')->default(true);
            $table->json('business_hours')->nullable(); // Store opening/closing hours
            $table->decimal('delivery_radius', 8, 2)->nullable(); // In kilometers
            $table->decimal('minimum_order_amount', 10, 2)->default(0);
            $table->decimal('delivery_fee', 8, 2)->default(0);
            $table->text('rejection_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('provider_id')->references('id')->on('users')->onDelete('cascade');
            // Note: Removing foreign key constraints due to existing table structure differences
            // $table->foreign('country_id')->references('id')->on('countries')->onDelete('set null');
            // $table->foreign('state_id')->references('id')->on('states')->onDelete('set null');
            // $table->foreign('city_id')->references('id')->on('cities')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['provider_id', 'status']);
            $table->index(['status', 'is_active']);
            $table->index(['latitude', 'longitude']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stores');
    }
};
