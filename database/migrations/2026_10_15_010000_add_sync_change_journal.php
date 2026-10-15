<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
class AddSyncChangeJournal extends Migration
{
 public function up(){Schema::create('sync_changes',function(Blueprint $t){$t->id();$t->foreignId('daily_report_id')->constrained()->onDelete('no action');$t->unsignedBigInteger('plant_id');$t->unsignedBigInteger('owner_id');$t->unsignedInteger('server_version');$t->timestamps();$t->index(['plant_id','owner_id','id'],'sync_changes_scope');});DB::table('daily_reports')->orderBy('id')->chunkById(100,function($rows){foreach($rows as $r)DB::table('sync_changes')->insert(['daily_report_id'=>$r->id,'plant_id'=>$r->plant_id,'owner_id'=>$r->created_by,'server_version'=>$r->server_version,'created_at'=>now(),'updated_at'=>now()]);});}
 public function down(){Schema::dropIfExists('sync_changes');}
}
