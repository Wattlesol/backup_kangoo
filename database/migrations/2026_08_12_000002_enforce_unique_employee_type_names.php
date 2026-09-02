<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EnforceUniqueEmployeeTypeNames extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('handyman_types')) {
            return;
        }

        $duplicates = DB::table('handyman_types')
            ->selectRaw('LOWER(name) as normalized_name, MIN(id) as keep_id')
            ->whereNull('deleted_at')
            ->groupByRaw('LOWER(name)')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            DB::table('handyman_types')
                ->whereNull('deleted_at')
                ->whereRaw('LOWER(name) = ?', [$duplicate->normalized_name])
                ->where('id', '!=', $duplicate->keep_id)
                ->update(['deleted_at' => now(), 'updated_at' => now()]);
        }

        if (!Schema::hasColumn('handyman_types', 'normalized_name')) {
            Schema::table('handyman_types', function (Blueprint $table) {
                $table->string('normalized_name')->nullable()->after('name');
            });
        }

        if (!Schema::hasColumn('handyman_types', 'active_normalized_name')) {
            Schema::table('handyman_types', function (Blueprint $table) {
                $table->string('active_normalized_name')->nullable()->after('normalized_name');
            });
        }

        DB::table('handyman_types')->update([
            'normalized_name' => DB::raw('LOWER(name)'),
        ]);

        DB::table('handyman_types')
            ->whereNull('deleted_at')
            ->update(['active_normalized_name' => DB::raw('LOWER(name)')]);

        DB::table('handyman_types')
            ->whereNotNull('deleted_at')
            ->update(['active_normalized_name' => null]);

        Schema::table('handyman_types', function (Blueprint $table) {
            $table->unique('active_normalized_name', 'handyman_types_active_normalized_name_unique');
        });
    }

    public function down()
    {
        if (!Schema::hasTable('handyman_types')) {
            return;
        }

        Schema::table('handyman_types', function (Blueprint $table) {
            $table->dropUnique('handyman_types_active_normalized_name_unique');
        });

        if (Schema::hasColumn('handyman_types', 'active_normalized_name')) {
            Schema::table('handyman_types', function (Blueprint $table) {
                $table->dropColumn('active_normalized_name');
            });
        }

        if (Schema::hasColumn('handyman_types', 'normalized_name')) {
            Schema::table('handyman_types', function (Blueprint $table) {
                $table->dropColumn('normalized_name');
            });
        }
    }
}
