<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('qualitycontrol_comments', function (Blueprint $table) {
            $table->id();
            $table->string('quality_control_id')->nullable();
            $table->longText('comment')->nullable();
            $table->string('file')->nullable();
            $table->string('created_by')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qualitycontrol_comments');
    }
};
