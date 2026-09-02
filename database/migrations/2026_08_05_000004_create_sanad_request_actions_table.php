<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSanadRequestActionsTable extends Migration
{
    public function up()
    {
        Schema::create('sanad_request_actions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id')->index();
            $table->unsignedBigInteger('actor_id')->nullable()->index();
            $table->string('actor_role')->nullable()->index();
            $table->string('action')->index();
            $table->string('previous_status')->nullable();
            $table->string('current_status')->nullable();
            $table->string('previous_stage')->nullable();
            $table->string('current_stage')->nullable();
            $table->text('reason')->nullable();
            $table->text('internal_note')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('sanad_request_actions');
    }
}
