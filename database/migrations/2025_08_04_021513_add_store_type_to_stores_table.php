<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStoreTypeToStoresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stores', function (Blueprint $table) {
            // Add store_type column if it doesn't exist
            if (!Schema::hasColumn('stores', 'store_type')) {
                $table->enum('store_type', ['main', 'provider'])->default('provider')->after('provider_id');
            }

            // Add other missing columns that are in the model fillable
            if (!Schema::hasColumn('stores', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('id');
            }

            if (!Schema::hasColumn('stores', 'created_by_type')) {
                $table->string('created_by_type')->default('admin')->after('created_by');
            }

            if (!Schema::hasColumn('stores', 'email')) {
                $table->string('email')->nullable()->after('name');
            }

            if (!Schema::hasColumn('stores', 'store_settings')) {
                $table->json('store_settings')->nullable()->after('business_hours');
            }

            if (!Schema::hasColumn('stores', 'payment_methods')) {
                $table->json('payment_methods')->nullable()->after('store_settings');
            }

            if (!Schema::hasColumn('stores', 'shipping_methods')) {
                $table->json('shipping_methods')->nullable()->after('payment_methods');
            }

            if (!Schema::hasColumn('stores', 'terms_and_conditions')) {
                $table->text('terms_and_conditions')->nullable()->after('shipping_methods');
            }

            if (!Schema::hasColumn('stores', 'privacy_policy')) {
                $table->text('privacy_policy')->nullable()->after('terms_and_conditions');
            }

            if (!Schema::hasColumn('stores', 'return_policy')) {
                $table->text('return_policy')->nullable()->after('privacy_policy');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('stores', function (Blueprint $table) {
            // Drop the columns we added
            $table->dropColumn([
                'store_type',
                'created_by',
                'created_by_type',
                'email',
                'store_settings',
                'payment_methods',
                'shipping_methods',
                'terms_and_conditions',
                'privacy_policy',
                'return_policy'
            ]);
        });
    }
}
