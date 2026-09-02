<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddUniqueMainStoreConstraintToStoresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // For single-store architecture, we rely on application-level validation
        // to prevent multiple main stores. Database constraints for this specific
        // case are complex in MySQL, so we handle it in the controller logic.

        // This migration serves as documentation that only one main store should exist
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // No database changes to reverse
    }
}
