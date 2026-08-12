<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sanad_ai_knowledge_chunks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('knowledge_item_id')->index();
            $table->unsignedInteger('chunk_index')->default(0);
            $table->longText('content');
            $table->json('embedding')->nullable();
            $table->string('embedding_model')->nullable();
            $table->string('vector_id')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('knowledge_item_id')
                ->references('id')
                ->on('sanad_ai_knowledge_items')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sanad_ai_knowledge_chunks');
    }
};
