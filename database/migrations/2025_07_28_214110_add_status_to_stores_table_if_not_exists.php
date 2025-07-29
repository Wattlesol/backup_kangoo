<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddStatusToStoresTableIfNotExists extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stores', function (Blueprint $table) {
            // Add status column if it doesn't exist
            if (!Schema::hasColumn('stores', 'status')) {
                $table->enum('status', ['pending', 'approved', 'rejected', 'suspended'])->default('approved')->after('longitude');
            }

            // Add other missing columns if they don't exist
            if (!Schema::hasColumn('stores', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('status');
            }

            if (!Schema::hasColumn('stores', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('rejection_reason');
            }

            if (!Schema::hasColumn('stores', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('approved_at');
            }
        });

        // Update existing stores to have approved status
        DB::table('stores')->whereNull('status')->update(['status' => 'approved']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('stores', function (Blueprint $table) {
            if (Schema::hasColumn('stores', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('stores', 'rejection_reason')) {
                $table->dropColumn('rejection_reason');
            }
            if (Schema::hasColumn('stores', 'approved_at')) {
                $table->dropColumn('approved_at');
            }
            if (Schema::hasColumn('stores', 'approved_by')) {
                $table->dropColumn('approved_by');
            }
        });
    }
}
