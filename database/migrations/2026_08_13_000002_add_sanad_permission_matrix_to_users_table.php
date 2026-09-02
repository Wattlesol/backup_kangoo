<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSanadPermissionMatrixToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'sanad_permission_matrix')) {
                $table->json('sanad_permission_matrix')->nullable()->after('sanad_permissions');
            }
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'sanad_permission_matrix')) {
                $table->dropColumn('sanad_permission_matrix');
            }
        });
    }
}
