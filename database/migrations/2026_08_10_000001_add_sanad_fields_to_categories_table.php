<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSanadFieldsToCategoriesTable extends Migration
{
    public function up()
    {
        Schema::table('categories', function (Blueprint $table) {
            if (!Schema::hasColumn('categories', 'name_ar')) {
                $table->string('name_ar', 150)->nullable()->after('name');
            }
            if (!Schema::hasColumn('categories', 'name_en')) {
                $table->string('name_en', 150)->nullable()->after('name_ar');
            }
            if (!Schema::hasColumn('categories', 'icon')) {
                $table->string('icon')->nullable()->after('color');
            }
            if (!Schema::hasColumn('categories', 'display_order')) {
                $table->unsignedInteger('display_order')->default(0)->after('icon')->index();
            }
        });
    }

    public function down()
    {
        Schema::table('categories', function (Blueprint $table) {
            foreach (['name_ar', 'name_en', 'icon', 'display_order'] as $column) {
                if (Schema::hasColumn('categories', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
