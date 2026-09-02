<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWorkScheduleToUsersTable extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('users', 'sanad_work_schedule')) {
            Schema::table('users', function (Blueprint $table) {
                $table->json('sanad_work_schedule')->nullable()->after('sanad_working_hours');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('users', 'sanad_work_schedule')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('sanad_work_schedule');
            });
        }
    }
}
