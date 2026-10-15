<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class AddBreakdownClientUuid extends Migration
{
 public function up(){Schema::table('breakdown_reports',function(Blueprint $t){$t->uuid('client_uuid')->nullable();});if(DB::getDriverName()==='sqlsrv')DB::statement('CREATE UNIQUE INDEX breakdown_client_uuid ON breakdown_reports(client_uuid) WHERE client_uuid IS NOT NULL');else Schema::table('breakdown_reports',function(Blueprint $t){$t->unique('client_uuid','breakdown_client_uuid');});DB::table('breakdown_reports')->whereNull('client_uuid')->orderBy('id')->chunkById(100,function($rows){foreach($rows as $r)DB::table('breakdown_reports')->where('id',$r->id)->update(['client_uuid'=>(string)Str::uuid()]);});}
 public function down(){Schema::table('breakdown_reports',function(Blueprint $t){$t->dropUnique('breakdown_client_uuid');$t->dropColumn('client_uuid');});}
}
