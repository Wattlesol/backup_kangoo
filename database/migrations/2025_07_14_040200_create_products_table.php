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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('short_description')->nullable();
            $table->string('slug')->unique();
            $table->string('sku')->unique();
            $table->unsignedBigInteger('product_category_id');
            $table->unsignedBigInteger('created_by'); // Admin or Provider who created
            $table->enum('created_by_type', ['admin', 'provider'])->default('admin');
            $table->decimal('base_price', 10, 2); // Base price set by creator
            $table->decimal('admin_override_price', 10, 2)->nullable(); // Admin can override
            $table->boolean('admin_price_active')->default(false); // Whether admin override is active
            $table->enum('price_override_type', ['lowest', 'highest', 'fixed'])->default('lowest');
            $table->decimal('weight', 8, 2)->nullable();
            $table->json('dimensions')->nullable(); // {length, width, height}
            $table->boolean('track_inventory')->default(true);
            $table->integer('stock_quantity')->default(0);
            $table->integer('low_stock_threshold')->default(10);
            $table->boolean('is_featured')->default(false);
            $table->boolean('status')->default(true);
            $table->json('meta_data')->nullable(); // SEO and additional data
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('product_category_id')->references('id')->on('product_categories')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            
            $table->index(['product_category_id', 'status']);
            $table->index(['created_by', 'created_by_type']);
            $table->index(['is_featured', 'status']);
            $table->index('stock_quantity');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('products');
    }
};
