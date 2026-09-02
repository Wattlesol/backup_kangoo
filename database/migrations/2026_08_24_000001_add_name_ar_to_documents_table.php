<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddNameArToDocumentsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('documents', 'name_ar')) {
            Schema::table('documents', function (Blueprint $table) {
                $table->string('name_ar', 100)->nullable()->after('name');
            });
        }

        $translations = [
            'Commercial Registration' => 'السجل التجاري',
            'Business License' => 'الرخصة التجارية',
            'VAT Certificate' => 'شهادة ضريبة القيمة المضافة',
            'IBAN / Bank Proof' => 'إثبات الآيبان / الحساب البنكي',
            'Authorization Letter' => 'خطاب التفويض',
        ];

        foreach ($translations as $name => $nameAr) {
            DB::table('documents')
                ->where('name', $name)
                ->where(function ($query) {
                    $query->whereNull('name_ar')->orWhere('name_ar', '');
                })
                ->update(['name_ar' => $nameAr]);
        }
    }

    public function down()
    {
        if (Schema::hasColumn('documents', 'name_ar')) {
            Schema::table('documents', function (Blueprint $table) {
                $table->dropColumn('name_ar');
            });
        }
    }
}
