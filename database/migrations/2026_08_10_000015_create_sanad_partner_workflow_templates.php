<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSanadPartnerWorkflowTemplates extends Migration
{
    public function up()
    {
        Schema::create('sanad_partner_workflow_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['provider_id', 'is_active']);
        });

        Schema::create('sanad_partner_workflow_template_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_template_id')->constrained('sanad_partner_workflow_templates')->cascadeOnDelete();
            $table->string('stage_name');
            $table->string('role')->nullable();
            $table->unsignedInteger('execution_order')->default(1);
            $table->unsignedInteger('parallel_group')->nullable();
            $table->unsignedInteger('estimated_duration_minutes')->nullable();
            $table->json('required_skills')->nullable();
            $table->timestamps();
            $table->index(['workflow_template_id', 'execution_order']);
        });

        Schema::create('sanad_partner_service_workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->foreignId('workflow_template_id')->constrained('sanad_partner_workflow_templates')->cascadeOnDelete();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->unique(['provider_id', 'service_id', 'workflow_template_id'], 'sanad_partner_service_workflow_unique');
        });

        Schema::table('sanad_partner_workflow_stages', function (Blueprint $table) {
            $table->foreignId('workflow_template_id')->nullable()->after('booking_id')->constrained('sanad_partner_workflow_templates')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('sanad_partner_workflow_stages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('workflow_template_id');
        });
        Schema::dropIfExists('sanad_partner_service_workflows');
        Schema::dropIfExists('sanad_partner_workflow_template_steps');
        Schema::dropIfExists('sanad_partner_workflow_templates');
    }
}
