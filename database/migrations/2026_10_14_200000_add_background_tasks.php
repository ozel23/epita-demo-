<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class AddBackgroundTasks extends Migration
{
    public function up()
    {
        Schema::create('background_tasks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->onDelete('no action');
            $t->foreignId('plant_id')->nullable()->constrained()->onDelete('no action');
            $t->string('type');
            $t->string('status')->default('QUEUED');
            $t->json('parameters');
            $t->string('path')->nullable();
            $t->text('error')->nullable();
            $t->timestamps();
        });
        Schema::table('daily_report_attachments', function (Blueprint $t) {
            $t->string('thumbnail_path')->nullable();
        });
        Schema::table('users', function (Blueprint $t) {
            $t->unsignedInteger('security_version')->default(1);
        });
        Schema::table('personal_access_tokens', function (Blueprint $t) {
            $t->unsignedInteger('security_version')->default(1);
        });
    }
    public function down()
    {
        Schema::dropIfExists('background_tasks');
        Schema::table('daily_report_attachments', function (Blueprint $t) {
            $t->dropColumn('thumbnail_path');
        });
        Schema::table('users', function (Blueprint $t) {
            $t->dropColumn('security_version');
        });
        Schema::table('personal_access_tokens', function (Blueprint $t) {
            $t->dropColumn('security_version');
        });
    }
}
