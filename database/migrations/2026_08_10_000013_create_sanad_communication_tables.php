<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sanad_chat_threads', function (Blueprint $table) {
            $table->timestamp('last_message_at')->nullable()->index();
            $table->unsignedBigInteger('closed_by')->nullable()->index();
            $table->timestamp('closed_at')->nullable();
        });
        Schema::table('sanad_chat_messages', function (Blueprint $table) {
            $table->unsignedBigInteger('recipient_id')->nullable()->index();
            $table->string('message_type')->default('text')->index();
            $table->unsignedBigInteger('document_request_id')->nullable()->index();
        });
        Schema::create('sanad_document_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id')->index();
            $table->unsignedBigInteger('service_id')->nullable()->index();
            $table->string('document_key')->nullable()->index();
            $table->string('document_name');
            $table->string('requested_from')->index();
            $table->unsignedBigInteger('requested_from_user_id')->nullable()->index();
            $table->unsignedBigInteger('requested_by')->index();
            $table->text('reason')->nullable();
            $table->text('instructions')->nullable();
            $table->boolean('required')->default(true);
            $table->date('due_at')->nullable()->index();
            $table->string('status')->default('pending')->index();
            $table->unsignedBigInteger('document_id')->nullable()->index();
            $table->unsignedBigInteger('reviewed_by')->nullable()->index();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sanad_document_requests');
        Schema::table('sanad_chat_messages', function (Blueprint $table) {
            $table->dropColumn(['recipient_id', 'message_type', 'document_request_id']);
        });
        Schema::table('sanad_chat_threads', function (Blueprint $table) {
            $table->dropColumn(['last_message_at', 'closed_by', 'closed_at']);
        });
    }
};
