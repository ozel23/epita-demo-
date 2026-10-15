<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class AddAssignmentHelpersAndMaintenanceAttachments extends Migration
{
    public function up()
    {
        Schema::create('assignment_personnels', function (Blueprint $t) {
            $t->id();
            $t->foreignId('assignment_id')->constrained()->onDelete('no action');
            $t->foreignId('personnel_id')->constrained('personnels')->onDelete('no action');
            $t->unique(['assignment_id', 'personnel_id']);
            $t->timestamps();
        });
        Schema::create('maintenance_attachments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('maintenance_work_order_id')->constrained()->onDelete('no action');
            $t->foreignId('uploaded_by')->constrained('users')->onDelete('no action');
            $t->string('path');
            $t->string('thumbnail_path')->nullable();
            $t->string('mime_type');
            $t->unsignedInteger('size');
            $t->timestamps();
        });
    }
    public function down()
    {
        Schema::dropIfExists('maintenance_attachments');
        Schema::dropIfExists('assignment_personnels');
    }
}
