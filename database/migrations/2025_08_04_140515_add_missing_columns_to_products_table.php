<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMissingColumnsToProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            // Add provider_id column (nullable to allow admin-created products)
            $table->unsignedBigInteger('provider_id')->nullable()->after('created_by_type');

            // Add missing order quantity columns
            $table->integer('minimum_order_quantity')->default(1)->after('stock_quantity');
            $table->integer('maximum_order_quantity')->nullable()->after('minimum_order_quantity');

            // Add provider notes column
            $table->text('provider_notes')->nullable()->after('is_available');

            // Add foreign key constraint for provider_id
            $table->foreign('provider_id')->references('id')->on('users')->onDelete('set null');

            // Add indexes for better performance
            $table->index(['provider_id', 'approval_status']);
            $table->index(['created_by', 'provider_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            // Drop foreign key and indexes first
            $table->dropForeign(['provider_id']);
            $table->dropIndex(['provider_id', 'approval_status']);
            $table->dropIndex(['created_by', 'provider_id']);

            // Drop the columns
            $table->dropColumn([
                'provider_id',
                'minimum_order_quantity',
                'maximum_order_quantity',
                'provider_notes'
            ]);
        });
    }
}
