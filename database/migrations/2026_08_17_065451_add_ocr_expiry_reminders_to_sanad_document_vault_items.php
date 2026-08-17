<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sanad_document_vault_items', function (Blueprint $table) {
            if (!Schema::hasColumn('sanad_document_vault_items', 'expiry_date')) {
                $table->date('expiry_date')->nullable()->after('retention_until')->index();
            }
            if (!Schema::hasColumn('sanad_document_vault_items', 'expiry_reminder_at')) {
                $table->date('expiry_reminder_at')->nullable()->after('expiry_date')->index();
            }
            if (!Schema::hasColumn('sanad_document_vault_items', 'expiry_reminder_enabled')) {
                $table->boolean('expiry_reminder_enabled')->default(true)->after('expiry_reminder_at')->index();
            }
            if (!Schema::hasColumn('sanad_document_vault_items', 'expiry_reminder_sent_at')) {
                $table->timestamp('expiry_reminder_sent_at')->nullable()->after('expiry_reminder_enabled')->index();
            }
            if (!Schema::hasColumn('sanad_document_vault_items', 'ocr_status')) {
                $table->string('ocr_status')->default('pending')->after('expiry_reminder_sent_at')->index();
            }
            if (!Schema::hasColumn('sanad_document_vault_items', 'ocr_confidence')) {
                $table->decimal('ocr_confidence', 5, 2)->nullable()->after('ocr_status');
            }
            if (!Schema::hasColumn('sanad_document_vault_items', 'ocr_metadata')) {
                $table->json('ocr_metadata')->nullable()->after('ocr_confidence');
            }
            if (!Schema::hasColumn('sanad_document_vault_items', 'ocr_processed_at')) {
                $table->timestamp('ocr_processed_at')->nullable()->after('ocr_metadata');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sanad_document_vault_items', function (Blueprint $table) {
            foreach ([
                'expiry_date',
                'expiry_reminder_at',
                'expiry_reminder_enabled',
                'expiry_reminder_sent_at',
                'ocr_status',
                'ocr_confidence',
                'ocr_metadata',
                'ocr_processed_at',
            ] as $column) {
                if (Schema::hasColumn('sanad_document_vault_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
