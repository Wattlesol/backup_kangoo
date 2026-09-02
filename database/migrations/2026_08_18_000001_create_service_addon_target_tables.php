<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateServiceAddonTargetTables extends Migration
{
    public function up()
    {
        Schema::create('service_addon_category', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_addon_id');
            $table->unsignedBigInteger('category_id');
            $table->timestamps();

            $table->unique(['service_addon_id', 'category_id'], 'service_addon_category_unique');
            $table->foreign('service_addon_id')->references('id')->on('service_addons')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
        });

        Schema::create('service_addon_service', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_addon_id');
            $table->unsignedBigInteger('service_id');
            $table->timestamps();

            $table->unique(['service_addon_id', 'service_id'], 'service_addon_service_unique');
            $table->foreign('service_addon_id')->references('id')->on('service_addons')->onDelete('cascade');
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
        });

        if (Schema::hasColumn('service_addons', 'service_id')) {
            $now = now();
            $legacyRows = DB::table('service_addons')
                ->whereNotNull('service_id')
                ->where('service_id', '>', 0)
                ->get(['id', 'service_id']);

            foreach ($legacyRows as $row) {
                if (!DB::table('services')->where('id', $row->service_id)->exists()) {
                    continue;
                }

                DB::table('service_addon_service')->updateOrInsert(
                    ['service_addon_id' => $row->id, 'service_id' => $row->service_id],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            }
        }
    }

    public function down()
    {
        Schema::dropIfExists('service_addon_service');
        Schema::dropIfExists('service_addon_category');
    }
}
