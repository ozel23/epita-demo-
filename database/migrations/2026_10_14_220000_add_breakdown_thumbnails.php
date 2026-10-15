<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class AddBreakdownThumbnails extends Migration
{
    public function up()
    {
        Schema::table('breakdown_attachments', function (Blueprint $t) {
            $t->string('thumbnail_path')->nullable();
        });
    }
    public function down()
    {
        Schema::table('breakdown_attachments', function (Blueprint $t) {
            $t->dropColumn('thumbnail_path');
        });
    }
}
