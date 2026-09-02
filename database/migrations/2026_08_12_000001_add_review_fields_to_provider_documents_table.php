<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddReviewFieldsToProviderDocumentsTable extends Migration
{
    public function up()
    {
        Schema::table('provider_documents', function (Blueprint $table) {
            if (!Schema::hasColumn('provider_documents', 'verification_status')) {
                $table->string('verification_status')->default('pending')->after('is_verified');
            }
            if (!Schema::hasColumn('provider_documents', 'review_reason')) {
                $table->text('review_reason')->nullable()->after('verification_status');
            }
            if (!Schema::hasColumn('provider_documents', 'reviewed_by')) {
                $table->unsignedBigInteger('reviewed_by')->nullable()->after('review_reason');
            }
            if (!Schema::hasColumn('provider_documents', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            }
        });

        DB::table('provider_documents')->where('is_verified', 1)->update(['verification_status' => 'approved']);
    }

    public function down()
    {
        Schema::table('provider_documents', function (Blueprint $table) {
            foreach (['reviewed_at', 'reviewed_by', 'review_reason', 'verification_status'] as $column) {
                if (Schema::hasColumn('provider_documents', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
