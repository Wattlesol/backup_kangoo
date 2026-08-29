<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sanad_ai_knowledge_items', function (Blueprint $table) {
            if (!Schema::hasColumn('sanad_ai_knowledge_items', 'title_ar')) {
                $table->string('title_ar')->nullable()->after('title');
            }
            if (!Schema::hasColumn('sanad_ai_knowledge_items', 'category_ar')) {
                $table->string('category_ar')->nullable()->after('category');
            }
            if (!Schema::hasColumn('sanad_ai_knowledge_items', 'content_ar')) {
                $table->longText('content_ar')->nullable()->after('content');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sanad_ai_knowledge_items', function (Blueprint $table) {
            foreach (['title_ar', 'category_ar', 'content_ar'] as $column) {
                if (Schema::hasColumn('sanad_ai_knowledge_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
