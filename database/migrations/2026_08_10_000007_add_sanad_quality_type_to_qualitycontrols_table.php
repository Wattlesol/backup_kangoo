<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qualitycontrols', function (Blueprint $table) {
            if (! Schema::hasColumn('qualitycontrols', 'issue_type')) {
                $table->string('issue_type')->default('customer_complaint')->after('title')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('qualitycontrols', function (Blueprint $table) {
            if (Schema::hasColumn('qualitycontrols', 'issue_type')) {
                $table->dropColumn('issue_type');
            }
        });
    }
};
