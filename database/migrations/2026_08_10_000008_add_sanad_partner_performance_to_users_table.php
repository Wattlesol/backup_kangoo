<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['sanad_quality_score', 'sanad_sla_compliance_rate', 'sanad_acceptance_rate', 'sanad_cancellation_rate', 'sanad_average_completion_minutes'] as $column) {
                if (! Schema::hasColumn('users', $column)) {
                    $table->decimal($column, 8, 2)->nullable()->after('sanad_daily_capacity');
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['sanad_quality_score', 'sanad_sla_compliance_rate', 'sanad_acceptance_rate', 'sanad_cancellation_rate', 'sanad_average_completion_minutes'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
