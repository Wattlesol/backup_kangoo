<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('sanad_assignment_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('recommended_provider_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('selected_provider_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('assignment_mode')->default('suggested');
            $table->string('status')->default('recommended');
            $table->text('reason')->nullable();
            $table->json('score_snapshot')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('sanad_assignment_decisions'); }
};
