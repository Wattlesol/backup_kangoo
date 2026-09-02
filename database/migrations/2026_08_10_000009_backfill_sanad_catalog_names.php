<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('categories')) {
            DB::table('categories')
                ->whereNull('name_en')
                ->whereNotNull('name')
                ->update(['name_en' => DB::raw('name')]);
            DB::table('categories')
                ->whereNull('name_ar')
                ->whereNotNull('name')
                ->update(['name_ar' => DB::raw('name')]);
        }

        if (Schema::hasTable('services')) {
            DB::table('services')
                ->whereNull('name_en')
                ->whereNotNull('name')
                ->update(['name_en' => DB::raw('name')]);
            DB::table('services')
                ->whereNull('name_ar')
                ->whereNotNull('name')
                ->update(['name_ar' => DB::raw('name')]);
        }
    }

    public function down(): void
    {
        // Backfilled values are retained to avoid making existing catalog records invalid again.
    }
};
