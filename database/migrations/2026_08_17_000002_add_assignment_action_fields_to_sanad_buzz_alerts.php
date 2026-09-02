<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sanad_buzz_alerts', function (Blueprint $table) {
            if (!Schema::hasColumn('sanad_buzz_alerts', 'action_type')) {
                $table->string('action_type')->nullable()->after('message')->index();
            }
            if (!Schema::hasColumn('sanad_buzz_alerts', 'action_status')) {
                $table->string('action_status')->nullable()->after('action_type')->index();
            }
        });

        if (Schema::hasTable('sanad_chat_messages')) {
            DB::table('sanad_chat_messages')
                ->where('message_type', 'system_note')
                ->where('message', 'like', 'Chat assigned to %')
                ->update(['visible_to' => json_encode(['customer', 'user'])]);
        }
    }

    public function down(): void
    {
        Schema::table('sanad_buzz_alerts', function (Blueprint $table) {
            foreach (['action_status', 'action_type'] as $column) {
                if (Schema::hasColumn('sanad_buzz_alerts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
