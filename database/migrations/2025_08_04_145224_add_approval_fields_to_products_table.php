<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddApprovalFieldsToProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            // Add approval status field if it doesn't exist
            if (!Schema::hasColumn('products', 'approval_status')) {
                $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending')->after('status');
            }

            // Add approval timestamp fields if they don't exist
            if (!Schema::hasColumn('products', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approval_status');
            }

            if (!Schema::hasColumn('products', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('approved_at');
            }

            // Add rejection timestamp fields if they don't exist
            if (!Schema::hasColumn('products', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('approved_by');
            }

            if (!Schema::hasColumn('products', 'rejected_by')) {
                $table->unsignedBigInteger('rejected_by')->nullable()->after('rejected_at');
            }

            if (!Schema::hasColumn('products', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('rejected_by');
            }

            // Add admin notes field if it doesn't exist
            if (!Schema::hasColumn('products', 'admin_notes')) {
                $table->text('admin_notes')->nullable()->after('rejection_reason');
            }

            // Add indexes for better performance (only if columns were added)
            try {
                $table->index(['approval_status', 'created_by_type']);
            } catch (\Exception $e) {
                // Index might already exist, ignore
            }

            try {
                $table->index(['approved_at', 'approved_by']);
            } catch (\Exception $e) {
                // Index might already exist, ignore
            }

            try {
                $table->index(['rejected_at', 'rejected_by']);
            } catch (\Exception $e) {
                // Index might already exist, ignore
            }
        });

        // Update existing products to have approved status if they're admin-created
        DB::table('products')
            ->where('created_by_type', 'admin')
            ->whereNull('approval_status')
            ->update([
                'approval_status' => 'approved',
                'approved_at' => now(),
                'approved_by' => 1 // Assuming admin user ID is 1
            ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['rejected_by']);

            // Drop indexes
            $table->dropIndex(['approval_status', 'created_by_type']);
            $table->dropIndex(['approved_at', 'approved_by']);
            $table->dropIndex(['rejected_at', 'rejected_by']);

            // Drop columns
            $table->dropColumn([
                'approval_status',
                'approved_at',
                'approved_by',
                'rejected_at',
                'rejected_by',
                'rejection_reason',
                'admin_notes'
            ]);
        });
    }
}
