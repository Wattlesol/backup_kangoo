<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSanadFieldsToServicesTable extends Migration
{
    public function up()
    {
        Schema::table('services', function (Blueprint $table) {
            if (!Schema::hasColumn('services', 'name_ar')) {
                $table->string('name_ar')->nullable()->after('name');
            }
            if (!Schema::hasColumn('services', 'name_en')) {
                $table->string('name_en')->nullable()->after('name_ar');
            }
            if (!Schema::hasColumn('services', 'government_entity')) {
                $table->string('government_entity')->nullable()->after('description');
            }
            if (!Schema::hasColumn('services', 'required_documents')) {
                $table->json('required_documents')->nullable()->after('government_entity');
            }
            if (!Schema::hasColumn('services', 'estimated_completion_time')) {
                $table->string('estimated_completion_time')->nullable()->after('required_documents');
            }
            if (!Schema::hasColumn('services', 'government_fee')) {
                $table->decimal('government_fee', 12, 2)->nullable()->after('estimated_completion_time');
            }
            if (!Schema::hasColumn('services', 'service_fee')) {
                $table->decimal('service_fee', 12, 2)->nullable()->after('government_fee');
            }
            if (!Schema::hasColumn('services', 'service_instructions')) {
                $table->longText('service_instructions')->nullable()->after('service_fee');
            }
            if (!Schema::hasColumn('services', 'terms_and_conditions')) {
                $table->longText('terms_and_conditions')->nullable()->after('service_instructions');
            }
            if (!Schema::hasColumn('services', 'partner_availability_notes')) {
                $table->text('partner_availability_notes')->nullable()->after('terms_and_conditions');
            }
            if (!Schema::hasColumn('services', 'required_employee_skills')) {
                $table->json('required_employee_skills')->nullable()->after('partner_availability_notes');
            }
        });
    }

    public function down()
    {
        Schema::table('services', function (Blueprint $table) {
            $columns = [
                'name_ar',
                'name_en',
                'government_entity',
                'required_documents',
                'estimated_completion_time',
                'government_fee',
                'service_fee',
                'service_instructions',
                'terms_and_conditions',
                'partner_availability_notes',
                'required_employee_skills',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('services', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
