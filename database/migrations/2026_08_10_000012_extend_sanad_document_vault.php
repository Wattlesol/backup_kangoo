<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sanad_document_vault_items', function (Blueprint $table) {
            $table->unsignedBigInteger('service_id')->nullable()->after('booking_id')->index();
            $table->unsignedBigInteger('provider_id')->nullable()->after('service_id')->index();
            $table->string('document_key')->nullable()->after('document_type')->index();
            $table->boolean('required')->default(false)->after('document_key');
            $table->string('source')->default('request')->after('required')->index();
            $table->text('review_reason')->nullable()->after('approved_by');
            $table->unsignedBigInteger('reviewed_by')->nullable()->after('review_reason')->index();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
        });
    }

    public function down(): void
    {
        Schema::table('sanad_document_vault_items', function (Blueprint $table) {
            foreach (['service_id','provider_id','document_key','required','source','review_reason','reviewed_by','reviewed_at'] as $column) {
                $table->dropColumn($column);
            }
        });
    }
};
