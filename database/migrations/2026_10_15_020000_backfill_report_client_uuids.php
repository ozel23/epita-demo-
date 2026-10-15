<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class BackfillReportClientUuids extends Migration
{
 public function up(){DB::table('daily_reports')->whereNull('client_uuid')->orderBy('id')->chunkById(100,function($rows){foreach($rows as $r)DB::table('daily_reports')->where('id',$r->id)->update(['client_uuid'=>(string)Str::uuid()]);});}
 public function down(){}
}
