<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'ai_first_responder_enabled')) {
                $table->boolean('ai_first_responder_enabled')->default(true)->after('closed_at');
            }
            if (!Schema::hasColumn('bookings', 'ai_first_responder_disabled_by')) {
                $table->unsignedBigInteger('ai_first_responder_disabled_by')->nullable()->after('ai_first_responder_enabled');
            }
            if (!Schema::hasColumn('bookings', 'ai_first_responder_disabled_at')) {
                $table->timestamp('ai_first_responder_disabled_at')->nullable()->after('ai_first_responder_disabled_by');
            }
            if (!Schema::hasColumn('bookings', 'chat_owner_type')) {
                $table->string('chat_owner_type')->default('ai')->index()->after('ai_first_responder_disabled_at');
            }
            if (!Schema::hasColumn('bookings', 'chat_owner_user_id')) {
                $table->unsignedBigInteger('chat_owner_user_id')->nullable()->index()->after('chat_owner_type');
            }
            if (!Schema::hasColumn('bookings', 'chat_assigned_by')) {
                $table->unsignedBigInteger('chat_assigned_by')->nullable()->after('chat_owner_user_id');
            }
            if (!Schema::hasColumn('bookings', 'chat_assigned_at')) {
                $table->timestamp('chat_assigned_at')->nullable()->after('chat_assigned_by');
            }
            if (!Schema::hasColumn('bookings', 'chat_assignment_note')) {
                $table->text('chat_assignment_note')->nullable()->after('chat_assigned_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            foreach ([
                'chat_assignment_note',
                'chat_assigned_at',
                'chat_assigned_by',
                'chat_owner_user_id',
                'chat_owner_type',
                'ai_first_responder_disabled_at',
                'ai_first_responder_disabled_by',
                'ai_first_responder_enabled',
            ] as $column) {
                if (Schema::hasColumn('bookings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
