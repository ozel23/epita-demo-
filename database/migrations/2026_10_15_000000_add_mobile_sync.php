<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
class AddMobileSync extends Migration
{
 public function up(){
  Schema::table('daily_reports',function(Blueprint $t){$t->uuid('client_uuid')->nullable();$t->unsignedInteger('server_version')->default(1);$t->index(['updated_at','id'],'reports_sync_updated');});
  if(DB::getDriverName()==='sqlsrv') DB::statement('CREATE UNIQUE INDEX reports_client_uuid ON daily_reports(client_uuid) WHERE client_uuid IS NOT NULL');
  else Schema::table('daily_reports',function(Blueprint $t){$t->unique('client_uuid','reports_client_uuid');});
  Schema::create('sync_receipts',function(Blueprint $t){$t->id();$t->foreignId('user_id')->constrained()->onDelete('no action');$t->string('request_key',100);$t->string('request_hash',64);$t->json('response')->nullable();$t->unsignedSmallInteger('status_code')->default(200);$t->timestamps();$t->unique(['user_id','request_key'],'sync_receipt_owner_key');});
  Schema::create('sync_acknowledgements',function(Blueprint $t){$t->id();$t->foreignId('user_id')->constrained()->onDelete('no action');$t->string('device_id',100);$t->text('cursor');$t->timestamps();$t->unique(['user_id','device_id'],'sync_ack_owner_device');});
 }
 public function down(){Schema::dropIfExists('sync_acknowledgements');Schema::dropIfExists('sync_receipts');Schema::table('daily_reports',function(Blueprint $t){$t->dropIndex('reports_sync_updated');$t->dropUnique('reports_client_uuid');$t->dropColumn(['client_uuid','server_version']);});}
}
