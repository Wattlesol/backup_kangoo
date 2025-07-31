<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('theme_settings', function (Blueprint $table) {
            $table->id();
            $table->string('setting_group', 50)->index(); // 'brand_colors', 'role_colors'
            $table->string('setting_key', 50)->index();   // 'yellow_light', 'admin_light', etc.
            $table->string('setting_value', 20);          // Color hex value
            $table->string('setting_name', 100)->nullable(); // Human readable name
            $table->text('description')->nullable();       // Description for admin
            $table->integer('sort_order')->default(0);     // For ordering in admin
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Composite unique index for group + key
            $table->unique(['setting_group', 'setting_key']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('theme_settings');
    }
};
