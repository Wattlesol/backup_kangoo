<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSanadFoundationTables extends Migration
{
    public function up()
    {
        Schema::create('sanad_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('actor_id')->nullable()->index();
            $table->string('actor_role')->nullable();
            $table->string('action')->index();
            $table->string('auditable_type')->nullable()->index();
            $table->unsignedBigInteger('auditable_id')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
        });

        Schema::create('sanad_buzz_alerts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id')->nullable()->index();
            $table->unsignedBigInteger('sender_id')->nullable()->index();
            $table->unsignedBigInteger('recipient_id')->nullable()->index();
            $table->string('recipient_role')->nullable()->index();
            $table->string('priority')->default('urgent');
            $table->string('status')->default('unread')->index();
            $table->text('message')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();
        });

        Schema::create('sanad_document_vault_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id')->nullable()->index();
            $table->unsignedBigInteger('owner_id')->nullable()->index();
            $table->unsignedBigInteger('uploaded_by')->nullable()->index();
            $table->string('document_type')->index();
            $table->string('verification_status')->default('pending')->index();
            $table->json('visible_to')->nullable();
            $table->string('file_name')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('retention_until')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sanad_chat_threads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id')->nullable()->index();
            $table->string('thread_type')->default('request')->index();
            $table->json('participant_roles')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->string('status')->default('open')->index();
            $table->timestamps();
        });

        Schema::create('sanad_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('thread_id')->index();
            $table->unsignedBigInteger('sender_id')->nullable()->index();
            $table->string('sender_role')->nullable();
            $table->text('message');
            $table->json('visible_to')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('sanad_ai_knowledge_items', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('category')->nullable()->index();
            $table->longText('content');
            $table->json('visible_to')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sanad_ai_interactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('booking_id')->nullable()->index();
            $table->text('question');
            $table->longText('answer')->nullable();
            $table->decimal('confidence', 5, 2)->nullable();
            $table->boolean('requires_escalation')->default(false)->index();
            $table->string('status')->default('answered')->index();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('sanad_ai_interactions');
        Schema::dropIfExists('sanad_ai_knowledge_items');
        Schema::dropIfExists('sanad_chat_messages');
        Schema::dropIfExists('sanad_chat_threads');
        Schema::dropIfExists('sanad_document_vault_items');
        Schema::dropIfExists('sanad_buzz_alerts');
        Schema::dropIfExists('sanad_audit_logs');
    }
}
