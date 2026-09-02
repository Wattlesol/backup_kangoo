<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddNameArAndNameEnToSubCategoriesTable extends Migration
{
    public function up()
    {
        Schema::table('sub_categories', function (Blueprint $table) {
            if (!Schema::hasColumn('sub_categories', 'name_ar')) {
                $table->string('name_ar', 150)->nullable()->after('name');
            }
            if (!Schema::hasColumn('sub_categories', 'name_en')) {
                $table->string('name_en', 150)->nullable()->after('name_ar');
            }
        });

        // Backfill existing records
        $records = DB::table('sub_categories')->get();
        foreach ($records as $record) {
            $nameEn = $record->name_en ?: $record->name;
            $nameAr = $record->name_ar;

            if (empty($nameAr)) {
                if (stripos($record->name, '5 year') !== false && stripos($record->name, 'renewal') !== false) {
                    $nameAr = 'تجديد لمدة 5 سنوات';
                } elseif (stripos($record->name, 'Home Support') !== false) {
                    $nameAr = 'الدعم المنزلي';
                } else {
                    $nameAr = $record->name;
                }
            }

            DB::table('sub_categories')->where('id', $record->id)->update([
                'name_en' => $nameEn,
                'name_ar' => $nameAr,
            ]);
        }
    }

    public function down()
    {
        Schema::table('sub_categories', function (Blueprint $table) {
            foreach (['name_ar', 'name_en'] as $column) {
                if (Schema::hasColumn('sub_categories', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
