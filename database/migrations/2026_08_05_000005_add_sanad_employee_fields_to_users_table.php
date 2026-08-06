<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSanadEmployeeFieldsToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'sanad_job_title')) {
                $table->string('sanad_job_title')->nullable()->after('designation');
            }
            if (!Schema::hasColumn('users', 'sanad_department')) {
                $table->string('sanad_department')->nullable()->after('sanad_job_title');
            }
            if (!Schema::hasColumn('users', 'sanad_employee_status')) {
                $table->string('sanad_employee_status')->nullable()->default('available')->after('sanad_department');
            }
            if (!Schema::hasColumn('users', 'sanad_permissions')) {
                $table->json('sanad_permissions')->nullable()->after('sanad_employee_status');
            }
            if (!Schema::hasColumn('users', 'sanad_working_hours')) {
                $table->string('sanad_working_hours')->nullable()->after('sanad_permissions');
            }
            if (!Schema::hasColumn('users', 'sanad_daily_capacity')) {
                $table->unsignedInteger('sanad_daily_capacity')->nullable()->after('sanad_working_hours');
            }
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = [
                'sanad_job_title',
                'sanad_department',
                'sanad_employee_status',
                'sanad_permissions',
                'sanad_working_hours',
                'sanad_daily_capacity',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
