<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSanadFieldsToBookingsTable extends Migration
{
    public function up()
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'sanad_reference')) {
                $table->string('sanad_reference')->nullable()->unique()->after('id');
            }
            if (!Schema::hasColumn('bookings', 'sanad_stage')) {
                $table->string('sanad_stage')->nullable()->index()->after('status');
            }
            if (!Schema::hasColumn('bookings', 'sanad_priority')) {
                $table->string('sanad_priority')->nullable()->default('normal')->after('sanad_stage');
            }
            if (!Schema::hasColumn('bookings', 'sla_due_at')) {
                $table->timestamp('sla_due_at')->nullable()->after('sanad_priority');
            }
            if (!Schema::hasColumn('bookings', 'assigned_by')) {
                $table->unsignedBigInteger('assigned_by')->nullable()->after('provider_id');
            }
            if (!Schema::hasColumn('bookings', 'assigned_at')) {
                $table->timestamp('assigned_at')->nullable()->after('assigned_by');
            }
            if (!Schema::hasColumn('bookings', 'escalated_at')) {
                $table->timestamp('escalated_at')->nullable()->after('assigned_at');
            }
            if (!Schema::hasColumn('bookings', 'closed_at')) {
                $table->timestamp('closed_at')->nullable()->after('escalated_at');
            }
        });
    }

    public function down()
    {
        Schema::table('bookings', function (Blueprint $table) {
            $columns = [
                'sanad_reference',
                'sanad_stage',
                'sanad_priority',
                'sla_due_at',
                'assigned_by',
                'assigned_at',
                'escalated_at',
                'closed_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('bookings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
