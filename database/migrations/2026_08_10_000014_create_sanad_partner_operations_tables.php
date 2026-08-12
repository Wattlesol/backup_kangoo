<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSanadPartnerOperationsTables extends Migration
{
    public function up()
    {
        Schema::create('sanad_partner_service_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->boolean('is_enabled')->default(false);
            $table->string('availability')->nullable();
            $table->string('estimated_execution_time')->nullable();
            $table->json('required_employee_skills')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamps();
            $table->unique(['provider_id', 'service_id'], 'sanad_partner_service_unique');
        });

        Schema::create('sanad_partner_workflow_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('stage_name');
            $table->string('role')->nullable();
            $table->unsignedInteger('execution_order')->default(1);
            $table->unsignedInteger('parallel_group')->nullable();
            $table->unsignedInteger('estimated_duration_minutes')->nullable();
            $table->json('depends_on_stage_ids')->nullable();
            $table->string('assignment_mode')->default('manual');
            $table->string('status')->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['booking_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('sanad_partner_workflow_stages');
        Schema::dropIfExists('sanad_partner_service_availabilities');
    }
}

