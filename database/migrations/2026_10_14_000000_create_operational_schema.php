<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class CreateOperationalSchema extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('username')->nullable()->unique();
            $t->boolean('active')->default(true);
            $t->string('personnel_number')->nullable();
            $t->string('contact')->nullable();
        });
        Schema::create('companies', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->string('name');
            $t->text('address')->nullable();
            $t->boolean('active')->default(true);
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('plants', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained('companies')->onDelete('no action');
            $t->string('code')->unique();
            $t->string('name');
            $t->boolean('active')->default(true);
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('plant_user', function (Blueprint $t) {
            $t->id();
            $t->foreignId('plant_id')->constrained('plants')->onDelete('no action');
            $t->foreignId('user_id')->constrained('users')->onDelete('no action');
            $t->unique(['plant_id', 'user_id']);
            $t->timestamps();
        });
        Schema::create('divisions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('plant_id')->constrained('plants')->onDelete('no action');
            $t->string('code');
            $t->string('name');
            $t->boolean('active')->default(true);
            $t->unique(['plant_id', 'code']);
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('locations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('plant_id')->constrained('plants')->onDelete('no action');
            $t->foreignId('division_id')->nullable()->constrained('divisions')->onDelete('no action');
            $t->string('code');
            $t->string('name');
            $t->string('location_type')->default('OP');
            $t->boolean('active')->default(true);
            $t->unique(['plant_id', 'code']);
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('personnels', function (Blueprint $t) {
            $t->id();
            $t->foreignId('plant_id')->constrained('plants')->onDelete('no action');
            $t->foreignId('user_id')->nullable()->constrained('users')->onDelete('no action');
            $t->string('personnel_number')->unique();
            $t->string('name');
            $t->string('type')->default('OPERATOR');
            $t->boolean('active')->default(true);
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('equipment_types', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->string('name');
            $t->boolean('active')->default(true);
            $t->string('prefix')->nullable();
            $t->string('meter_type')->default('KM');
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('activity_codes', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->string('name');
            $t->boolean('active')->default(true);
            $t->unsignedBigInteger('parent_id')->nullable();
            $t->string('sap_reason_code')->nullable();
            $t->boolean('productive')->default(true);
            $t->boolean('requires_photo')->default(false);
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('load_types', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->string('name');
            $t->boolean('active')->default(true);
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('work_units', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->string('name');
            $t->boolean('active')->default(true);
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('fuel_types', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->string('name');
            $t->boolean('active')->default(true);
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('fueling_locations', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->string('name');
            $t->boolean('active')->default(true);
            $t->foreignId('plant_id')->constrained('plants')->onDelete('no action');
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('non_moving_reasons', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->string('name');
            $t->boolean('active')->default(true);
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('spare_parts', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->string('name');
            $t->boolean('active')->default(true);
            $t->string('unit')->default('pcs');
            $t->decimal('unit_cost', 15, 2)->default(0);
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('equipment', function (Blueprint $t) {
            $t->id();
            $t->foreignId('plant_id')->constrained('plants')->onDelete('no action');
            $t->foreignId('equipment_type_id')->constrained('equipment_types')->onDelete('no action');
            $t->foreignId('default_operator_id')->nullable()->constrained('personnels')->onDelete('no action');
            $t->string('code')->unique();
            $t->string('name');
            $t->string('registration_number')->nullable();
            $t->string('brand')->nullable();
            $t->string('model')->nullable();
            $t->string('performance_unit')->nullable();
            $t->unsignedSmallInteger('year')->nullable();
            $t->decimal('capacity', 15, 2)->default(0);
            $t->decimal('last_meter', 15, 2)->default(0);
            $t->string('ownership')->default('OWN');
            $t->string('meter_type')->default('KM');
            $t->string('current_condition')->default('STANDBY');
            $t->boolean('active')->default(true);
            $t->index(['plant_id', 'current_condition']);
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('fuel_ratio_standards', function (Blueprint $t) {
            $t->id();
            $t->foreignId('equipment_type_id')->constrained('equipment_types')->onDelete('no action');
            $t->decimal('lower_limit', 15, 2)->default(0);
            $t->decimal('upper_limit', 15, 2)->default(0);
            $t->date('effective_date');
            $t->string('unit')->default('liter/km');
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('overtime_rules', function (Blueprint $t) {
            $t->id();
            $t->foreignId('plant_id')->constrained('plants')->onDelete('no action');
            $t->time('start_time');
            $t->decimal('tariff', 15, 2)->default(0);
            $t->unsignedTinyInteger('max_helpers')->default(4);
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('work_plans', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained('companies')->onDelete('no action');
            $t->foreignId('plant_id')->constrained('plants')->onDelete('no action');
            $t->foreignId('division_id')->nullable()->constrained('divisions')->onDelete('no action');
            $t->foreignId('location_id')->nullable()->constrained('locations')->onDelete('no action');
            $t->foreignId('created_by')->constrained('users')->onDelete('no action');
            $t->string('number')->unique();
            $t->date('start_date');
            $t->date('end_date');
            $t->string('status')->default('DRAFT');
            $t->text('notes')->nullable();
            $t->timestamps();
        });
        Schema::create('work_plan_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('work_plan_id')->constrained('work_plans')->onDelete('no action');
            $t->foreignId('activity_code_id')->constrained('activity_codes')->onDelete('no action');
            $t->foreignId('work_unit_id')->constrained('work_units')->onDelete('no action');
            $t->decimal('target_volume', 15, 2)->default(0);
            $t->unsignedInteger('equipment_requirement')->default(1);
            $t->timestamps();
        });
        Schema::create('assignments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('plant_id')->constrained('plants')->onDelete('no action');
            $t->foreignId('work_plan_id')->nullable()->constrained('work_plans')->onDelete('no action');
            $t->foreignId('equipment_id')->constrained('equipment')->onDelete('no action');
            $t->foreignId('operator_id')->constrained('personnels')->onDelete('no action');
            $t->foreignId('created_by')->constrained('users')->onDelete('no action');
            $t->dateTime('planned_start_at');
            $t->dateTime('planned_end_at');
            $t->string('shift')->default('1');
            $t->string('status')->default('ASSIGNED');
            $t->text('override_reason')->nullable();
            $t->index(['equipment_id', 'planned_start_at']);
            $t->timestamps();
        });
        Schema::create('daily_reports', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained('companies')->onDelete('no action');
            $t->foreignId('plant_id')->constrained('plants')->onDelete('no action');
            $t->foreignId('division_id')->nullable()->constrained('divisions')->onDelete('no action');
            $t->foreignId('location_id')->constrained('locations')->onDelete('no action');
            $t->foreignId('assignment_id')->nullable()->constrained('assignments')->onDelete('no action');
            $t->foreignId('equipment_id')->constrained('equipment')->onDelete('no action');
            $t->foreignId('main_operator_id')->constrained('personnels')->onDelete('no action');
            $t->foreignId('non_moving_reason_id')->nullable()->constrained('non_moving_reasons')->onDelete('no action');
            $t->string('report_number')->unique();
            $t->date('report_date');
            $t->string('shift')->default('1');
            $t->string('meter_type')->default('KM');
            $t->decimal('meter_start', 15, 2)->default(0);
            $t->decimal('meter_end', 15, 2)->default(0);
            $t->decimal('meter_total', 15, 2)->default(0);
            $t->dateTime('work_start_at');
            $t->dateTime('work_end_at');
            $t->unsignedInteger('work_minutes');
            $t->string('equipment_condition')->default('WORKING');
            $t->text('description')->nullable();
            $t->text('meter_override_reason')->nullable();
            $t->string('status')->default('DRAFT');
            $t->unsignedInteger('revision_no')->default(0);
            $t->dateTime('submitted_at')->nullable();
            $t->dateTime('verified_at')->nullable();
            $t->dateTime('approved_at')->nullable();
            $t->foreignId('verified_by')->nullable()->constrained('users')->onDelete('no action');
            $t->foreignId('approved_by')->nullable()->constrained('users')->onDelete('no action');
            $t->foreignId('created_by')->constrained('users')->onDelete('no action');
            $t->foreignId('updated_by')->constrained('users')->onDelete('no action');
            $t->index(['plant_id', 'report_date', 'status']);
            $t->index(['equipment_id', 'work_start_at', 'work_end_at']);
            $t->timestamps();
        });
        Schema::create('daily_report_personnels', function (Blueprint $t) {
            $t->id();
            $t->foreignId('daily_report_id')->constrained('daily_reports')->onDelete('no action');
            $t->foreignId('personnel_id')->constrained('personnels')->onDelete('no action');
            $t->string('role')->default('HELPER');
            $t->unique(['daily_report_id', 'personnel_id']);
            $t->timestamps();
        });
        Schema::create('daily_report_activities', function (Blueprint $t) {
            $t->id();
            $t->foreignId('daily_report_id')->constrained('daily_reports')->onDelete('no action');
            $t->foreignId('activity_code_id')->constrained('activity_codes')->onDelete('no action');
            $t->foreignId('load_type_id')->nullable()->constrained('load_types')->onDelete('no action');
            $t->foreignId('work_unit_id')->constrained('work_units')->onDelete('no action');
            $t->decimal('volume', 15, 2)->default(0);
            $t->text('description')->nullable();
            $t->timestamps();
        });
        Schema::create('daily_report_fuels', function (Blueprint $t) {
            $t->id();
            $t->foreignId('daily_report_id')->constrained('daily_reports')->onDelete('no action');
            $t->foreignId('fuel_type_id')->constrained('fuel_types')->onDelete('no action');
            $t->foreignId('fueling_location_id')->nullable()->constrained('fueling_locations')->onDelete('no action');
            $t->decimal('quantity_filled', 15, 2)->default(0);
            $t->decimal('quantity_used', 15, 2)->default(0);
            $t->decimal('quantity_remaining', 15, 2)->default(0);
            $t->decimal('ratio', 15, 4)->nullable();
            $t->boolean('abnormal')->default(false);
            $t->timestamps();
        });
        Schema::create('daily_report_attachments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('daily_report_id')->constrained('daily_reports')->onDelete('no action');
            $t->foreignId('uploaded_by')->constrained('users')->onDelete('no action');
            $t->string('path')->nullable();
            $t->string('mime_type')->nullable();
            $t->unsignedInteger('size');
            $t->timestamps();
        });
        Schema::create('approval_histories', function (Blueprint $t) {
            $t->id();
            $t->foreignId('daily_report_id')->constrained('daily_reports')->onDelete('no action');
            $t->foreignId('actor_id')->constrained('users')->onDelete('no action');
            $t->string('from_status')->nullable();
            $t->string('to_status')->nullable();
            $t->unsignedInteger('revision_no');
            $t->text('note')->nullable();
            $t->json('snapshot')->nullable();
            $t->string('ip')->nullable();
            $t->text('user_agent')->nullable();
            $t->timestamps();
        });
        Schema::create('breakdown_reports', function (Blueprint $t) {
            $t->id();
            $t->foreignId('plant_id')->constrained('plants')->onDelete('no action');
            $t->foreignId('equipment_id')->constrained('equipment')->onDelete('no action');
            $t->foreignId('location_id')->constrained('locations')->onDelete('no action');
            $t->foreignId('reported_by')->constrained('users')->onDelete('no action');
            $t->string('number')->unique();
            $t->dateTime('occurred_at');
            $t->decimal('meter', 15, 2)->default(0);
            $t->string('symptom')->nullable();
            $t->string('severity')->default('MEDIUM');
            $t->string('status')->default('REPORTED');
            $t->text('description');
            $t->timestamps();
        });
        Schema::create('breakdown_attachments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('breakdown_report_id')->constrained('breakdown_reports')->onDelete('no action');
            $t->foreignId('uploaded_by')->constrained('users')->onDelete('no action');
            $t->string('path')->nullable();
            $t->string('mime_type')->nullable();
            $t->unsignedInteger('size');
            $t->timestamps();
        });
        Schema::create('maintenance_work_orders', function (Blueprint $t) {
            $t->id();
            $t->foreignId('plant_id')->constrained('plants')->onDelete('no action');
            $t->foreignId('equipment_id')->constrained('equipment')->onDelete('no action');
            $t->foreignId('breakdown_report_id')->nullable()->constrained('breakdown_reports')->onDelete('no action');
            $t->foreignId('created_by')->constrained('users')->onDelete('no action');
            $t->foreignId('closed_by')->nullable()->constrained('users')->onDelete('no action');
            $t->string('number')->unique();
            $t->string('sap_order_number')->nullable();
            $t->string('type')->default('CORRECTIVE');
            $t->string('priority')->default('MEDIUM');
            $t->string('status')->default('OPEN');
            $t->text('diagnosis')->nullable();
            $t->text('result')->nullable();
            $t->dateTime('unavailable_at')->nullable();
            $t->dateTime('started_at')->nullable();
            $t->dateTime('ended_at')->nullable();
            $t->dateTime('released_at')->nullable();
            $t->unsignedInteger('downtime_minutes')->default(0);
            $t->date('next_service_date')->nullable();
            $t->decimal('next_service_meter', 15, 2)->default(0);
            $t->timestamps();
        });
        Schema::create('maintenance_technicians', function (Blueprint $t) {
            $t->id();
            $t->foreignId('maintenance_work_order_id')->constrained('maintenance_work_orders')->onDelete('no action');
            $t->foreignId('personnel_id')->constrained('personnels')->onDelete('no action');
            $t->unique(['maintenance_work_order_id', 'personnel_id']);
            $t->timestamps();
        });
        Schema::create('maintenance_actions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('maintenance_work_order_id')->constrained('maintenance_work_orders')->onDelete('no action');
            $t->foreignId('technician_id')->constrained('personnels')->onDelete('no action');
            $t->text('description');
            $t->unsignedInteger('labor_minutes')->default(0);
            $t->timestamps();
        });
        Schema::create('maintenance_part_usages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('maintenance_work_order_id')->constrained('maintenance_work_orders')->onDelete('no action');
            $t->foreignId('spare_part_id')->constrained('spare_parts')->onDelete('no action');
            $t->decimal('quantity', 15, 2)->default(0);
            $t->decimal('unit_cost', 15, 2)->default(0);
            $t->string('warehouse_reference')->nullable();
            $t->timestamps();
        });
        Schema::create('equipment_status_histories', function (Blueprint $t) {
            $t->id();
            $t->foreignId('equipment_id')->constrained('equipment')->onDelete('no action');
            $t->foreignId('actor_id')->constrained('users')->onDelete('no action');
            $t->text('reason');
            $t->string('from_condition')->nullable();
            $t->string('to_condition')->nullable();
            $t->timestamps();
        });
        Schema::create('equipment_location_histories', function (Blueprint $t) {
            $t->id();
            $t->foreignId('equipment_id')->constrained('equipment')->onDelete('no action');
            $t->foreignId('actor_id')->constrained('users')->onDelete('no action');
            $t->text('reason');
            $t->foreignId('location_id')->constrained('locations')->onDelete('no action');
            $t->timestamps();
        });
        Schema::create('sap_mapping_rules', function (Blueprint $t) {
            $t->id();
            $t->foreignId('plant_id')->constrained('plants')->onDelete('no action');
            $t->foreignId('activity_code_id')->constrained('activity_codes')->onDelete('no action');
            $t->string('order_number')->nullable();
            $t->string('operation_activity')->default('0010');
            $t->string('work_center')->nullable();
            $t->string('actual_work_unit')->default('KM');
            $t->string('value_source')->default('meter_total');
            $t->decimal('tolerance', 15, 2)->default(0);
            $t->unique(['plant_id', 'activity_code_id']);
            $t->timestamps();
        });
        Schema::create('sap_import_batches', function (Blueprint $t) {
            $t->id();
            $t->foreignId('plant_id')->constrained('plants')->onDelete('no action');
            $t->foreignId('created_by')->constrained('users')->onDelete('no action');
            $t->string('filename')->nullable();
            $t->string('status')->default('STAGED');
            $t->unsignedInteger('row_count')->default(0);
            $t->unsignedInteger('error_count')->default(0);
            $t->json('mapping')->nullable();
            $t->timestamps();
        });
        Schema::create('sap_iw47_staging_rows', function (Blueprint $t) {
            $t->id();
            $t->foreignId('sap_import_batch_id')->constrained('sap_import_batches')->onDelete('no action');
            $t->foreignId('plant_id')->constrained('plants')->onDelete('no action');
            $t->unsignedInteger('row_number');
            $t->json('raw_data');
            $t->json('normalized_data')->nullable();
            $t->json('errors')->nullable();
            $t->string('equipment_code')->nullable();
            $t->string('confirmation_number')->nullable();
            $t->string('confirmation_counter')->nullable();
            $t->date('posting_date')->nullable();
            $t->decimal('actual_work', 15, 2)->nullable();
            $t->string('actual_work_unit')->nullable();
            $t->string('status')->default('VALID');
            $t->index(['plant_id', 'equipment_code', 'posting_date']);
            $t->timestamps();
        });
        Schema::create('sap_reconciliations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('plant_id')->constrained('plants')->onDelete('no action');
            $t->foreignId('sap_import_batch_id')->constrained('sap_import_batches')->onDelete('no action');
            $t->foreignId('sap_iw47_staging_row_id')->nullable()->constrained('sap_iw47_staging_rows')->onDelete('no action');
            $t->foreignId('daily_report_id')->nullable()->constrained('daily_reports')->onDelete('no action');
            $t->string('status')->nullable();
            $t->decimal('local_value', 15, 2)->nullable();
            $t->decimal('sap_value', 15, 2)->nullable();
            $t->decimal('variance', 15, 2)->nullable();
            $t->text('reason')->nullable();
            $t->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('no action');
            $t->dateTime('reviewed_at')->nullable();
            $t->timestamps();
        });
        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained('users')->onDelete('no action');
            $t->foreignId('plant_id')->nullable()->constrained('plants')->onDelete('no action');
            $t->string('event')->nullable();
            $t->string('subject_type')->nullable();
            $t->unsignedBigInteger('subject_id')->nullable();
            $t->json('before')->nullable();
            $t->json('after')->nullable();
            $t->string('ip')->nullable();
            $t->index(['plant_id', 'created_at']);
            $t->timestamps();
        });
        Schema::create('notifications', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('type');
            $t->morphs('notifiable');
            $t->text('data');
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
        });
        Schema::create('jobs', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->string('queue')->index();
            $t->longText('payload');
            $t->unsignedTinyInteger('attempts');
            $t->unsignedInteger('reserved_at')->nullable();
            $t->unsignedInteger('available_at');
            $t->unsignedInteger('created_at');
        });
    }
    public function down()
    {
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('sap_reconciliations');
        Schema::dropIfExists('sap_iw47_staging_rows');
        Schema::dropIfExists('sap_import_batches');
        Schema::dropIfExists('sap_mapping_rules');
        Schema::dropIfExists('equipment_location_histories');
        Schema::dropIfExists('equipment_status_histories');
        Schema::dropIfExists('maintenance_part_usages');
        Schema::dropIfExists('maintenance_actions');
        Schema::dropIfExists('maintenance_technicians');
        Schema::dropIfExists('maintenance_work_orders');
        Schema::dropIfExists('breakdown_attachments');
        Schema::dropIfExists('breakdown_reports');
        Schema::dropIfExists('approval_histories');
        Schema::dropIfExists('daily_report_attachments');
        Schema::dropIfExists('daily_report_fuels');
        Schema::dropIfExists('daily_report_activities');
        Schema::dropIfExists('daily_report_personnels');
        Schema::dropIfExists('daily_reports');
        Schema::dropIfExists('assignments');
        Schema::dropIfExists('work_plan_items');
        Schema::dropIfExists('work_plans');
        Schema::dropIfExists('overtime_rules');
        Schema::dropIfExists('fuel_ratio_standards');
        Schema::dropIfExists('equipment');
        Schema::dropIfExists('spare_parts');
        Schema::dropIfExists('non_moving_reasons');
        Schema::dropIfExists('fueling_locations');
        Schema::dropIfExists('fuel_types');
        Schema::dropIfExists('work_units');
        Schema::dropIfExists('load_types');
        Schema::dropIfExists('activity_codes');
        Schema::dropIfExists('equipment_types');
        Schema::dropIfExists('personnels');
        Schema::dropIfExists('locations');
        Schema::dropIfExists('divisions');
        Schema::dropIfExists('plant_user');
        Schema::dropIfExists('plants');
        Schema::dropIfExists('companies');
    }
}
