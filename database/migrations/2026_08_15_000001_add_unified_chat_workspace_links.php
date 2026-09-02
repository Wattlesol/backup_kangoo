<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sanad_chat_messages', function (Blueprint $table) {
            if (!Schema::hasColumn('sanad_chat_messages', 'buzz_alert_id')) {
                $table->unsignedBigInteger('buzz_alert_id')->nullable()->index()->after('document_request_id');
            }
            if (!Schema::hasColumn('sanad_chat_messages', 'ai_interaction_id')) {
                $table->unsignedBigInteger('ai_interaction_id')->nullable()->index()->after('buzz_alert_id');
            }
        });

        Schema::table('sanad_buzz_alerts', function (Blueprint $table) {
            if (!Schema::hasColumn('sanad_buzz_alerts', 'reply_count')) {
                $table->unsignedInteger('reply_count')->default(0)->after('message');
            }
            if (!Schema::hasColumn('sanad_buzz_alerts', 'last_reply_at')) {
                $table->timestamp('last_reply_at')->nullable()->index()->after('reply_count');
            }
        });

        Schema::create('sanad_ai_review_examples', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ai_interaction_id')->index();
            $table->unsignedBigInteger('booking_id')->nullable()->index();
            $table->unsignedBigInteger('reviewed_by')->nullable()->index();
            $table->string('actor_role')->nullable()->index();
            $table->text('question');
            $table->longText('original_answer')->nullable();
            $table->longText('corrected_answer');
            $table->decimal('confidence', 5, 2)->nullable();
            $table->string('review_action')->index();
            $table->string('status')->default('ready')->index();
            $table->json('context_summary')->nullable();
            $table->json('sources')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('promoted_knowledge_item_id')->nullable()->index();
            $table->timestamp('promoted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sanad_ai_review_examples');

        Schema::table('sanad_buzz_alerts', function (Blueprint $table) {
            $table->dropColumn(['reply_count', 'last_reply_at']);
        });

        Schema::table('sanad_chat_messages', function (Blueprint $table) {
            $table->dropColumn(['buzz_alert_id', 'ai_interaction_id']);
        });
    }
};
