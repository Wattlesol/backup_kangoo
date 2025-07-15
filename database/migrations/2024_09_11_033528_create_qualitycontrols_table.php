<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('qualitycontrols', function (Blueprint $table) {
            $table->id();
            $table->integer('provider_id')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('status')->nullable()->default(0);
            $table->string('title')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qualitycontrols');
    }
};
