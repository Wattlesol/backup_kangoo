<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sanad_partner_service_performances')) {
            return;
        }

        Schema::create('sanad_partner_service_performances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->decimal('quality_score', 5, 2)->nullable();
            $table->decimal('sla_compliance_rate', 5, 2)->nullable();
            $table->decimal('acceptance_rate', 5, 2)->nullable();
            $table->decimal('cancellation_rate', 5, 2)->nullable();
            $table->decimal('average_completion_minutes', 10, 2)->nullable();
            $table->unsignedInteger('completed_orders')->default(0);
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();
            $table->unique(['provider_id', 'service_id']);
            $table->index(['service_id', 'quality_score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sanad_partner_service_performances');
    }
};
