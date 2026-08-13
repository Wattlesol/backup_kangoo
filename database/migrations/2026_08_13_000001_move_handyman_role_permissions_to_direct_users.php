<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        $handymanRole = DB::table('roles')->where('name', 'handyman')->first();

        if (!$handymanRole) {
            return;
        }

        $permissionIds = DB::table('role_has_permissions')
            ->where('role_id', $handymanRole->id)
            ->pluck('permission_id');

        if ($permissionIds->isEmpty()) {
            return;
        }

        $handymanIds = DB::table('users')->where('user_type', 'handyman')->pluck('id');

        foreach ($handymanIds as $userId) {
            $hasDirectPermissions = DB::table('model_has_permissions')
                ->where('model_type', App\Models\User::class)
                ->where('model_id', $userId)
                ->exists();

            if ($hasDirectPermissions) {
                continue;
            }

            foreach ($permissionIds as $permissionId) {
                DB::table('model_has_permissions')->insertOrIgnore([
                    'permission_id' => $permissionId,
                    'model_type' => App\Models\User::class,
                    'model_id' => $userId,
                ]);
            }
        }

        DB::table('role_has_permissions')
            ->where('role_id', $handymanRole->id)
            ->delete();

        if (Schema::hasTable('cache')) {
            DB::table('cache')->where('key', 'like', '%spatie.permission.cache%')->delete();
        }
    }

    public function down()
    {
        // Role permission defaults are intentionally not recreated; direct user permissions remain intact.
    }
};
