<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // categories: used on homepage filters
        if (Schema::hasTable('categories')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->index('status', 'idx_categories_status');
                $table->index('is_featured', 'idx_categories_is_featured');
                $table->index('name', 'idx_categories_name');
            });
        }
        // services: heavily filtered by category/status and sometimes provider
        if (Schema::hasTable('services')) {
            Schema::table('services', function (Blueprint $table) {
                $table->index(['category_id', 'status'], 'idx_services_category_status');
                $table->index('provider_id', 'idx_services_provider');
                $table->index('is_featured', 'idx_services_is_featured');
            });
        }
        // frontend_settings: landing page pulls by key+status
        if (Schema::hasTable('frontend_settings')) {
            Schema::table('frontend_settings', function (Blueprint $table) {
                $table->index('key', 'idx_frontend_settings_key');
                $table->index('status', 'idx_frontend_settings_status');
                $table->index('type', 'idx_frontend_settings_type');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('categories')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->dropIndex('idx_categories_status');
                $table->dropIndex('idx_categories_is_featured');
                $table->dropIndex('idx_categories_name');
            });
        }
        if (Schema::hasTable('services')) {
            Schema::table('services', function (Blueprint $table) {
                $table->dropIndex('idx_services_category_status');
                $table->dropIndex('idx_services_provider');
                $table->dropIndex('idx_services_is_featured');
            });
        }
        if (Schema::hasTable('frontend_settings')) {
            Schema::table('frontend_settings', function (Blueprint $table) {
                $table->dropIndex('idx_frontend_settings_key');
                $table->dropIndex('idx_frontend_settings_status');
                $table->dropIndex('idx_frontend_settings_type');
            });
        }
    }
};

