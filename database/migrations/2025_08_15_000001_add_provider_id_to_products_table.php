<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddProviderIdToProductsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'provider_id')) {
                $table->unsignedBigInteger('provider_id')->nullable()->after('created_by_type');

                // Add index for faster lookups
                $table->index('provider_id');

                // Add FK to users; set null on delete to retain product records
                $table->foreign('provider_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('set null');
            }
        });

        // Backfill provider_id for existing provider-created products
        try {
            DB::table('products')
                ->whereNull('provider_id')
                ->where('created_by_type', 'provider')
                ->update([
                    'provider_id' => DB::raw('created_by')
                ]);
        } catch (\Throwable $e) {
            // Log but do not fail migration if backfill has an issue
            \Log::warning('Backfill provider_id failed in migration: ' . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'provider_id')) {
                // Drop FK and index before dropping column
                try { $table->dropForeign(['provider_id']); } catch (\Throwable $e) {}
                try { $table->dropIndex(['provider_id']); } catch (\Throwable $e) {}
                $table->dropColumn('provider_id');
            }
        });
    }
}

