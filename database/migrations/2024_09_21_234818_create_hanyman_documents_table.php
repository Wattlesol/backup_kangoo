<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hanyman_documents', function (Blueprint $table) {
            $table->id();
            $table->integer('handyman_id')->nullable();
            $table->date('expired_date')->nullable();
            $table->string('file')->nullable();
            $table->longText('note')->nullable();
            $table->string('name');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hanyman_documents');
    }
};
