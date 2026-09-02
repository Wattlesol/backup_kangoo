<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'assignment_mode')) {
                $table->string('assignment_mode')->nullable()->default('suggested')->after('assigned_at');
            }
            if (! Schema::hasColumn('bookings', 'assignment_reason')) {
                $table->text('assignment_reason')->nullable()->after('assignment_mode');
            }
            if (! Schema::hasColumn('bookings', 'expected_completion_at')) {
                $table->timestamp('expected_completion_at')->nullable()->after('sla_due_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            foreach (['assignment_mode', 'assignment_reason', 'expected_completion_at'] as $column) {
                if (Schema::hasColumn('bookings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
