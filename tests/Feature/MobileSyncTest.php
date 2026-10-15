<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\DailyReport;
use App\Models\Equipment;
use App\Models\Personnel;
use App\Models\Location;
use App\Models\ActivityCode;
use App\Models\WorkUnit;
class MobileSyncTest extends TestCase
{
 use RefreshDatabase;
 protected function setUp():void{parent::setUp();$this->seed();}
 private function operator(){return User::where('username','operator')->firstOrFail();}
 private function payload(){ $e=Equipment::where('meter_type','KM')->orderBy('id')->first();return ['plant_id'=>$e->plant_id,'equipment_id'=>$e->id,'main_operator_id'=>Personnel::where('user_id',$this->operator()->id)->first()->id,'location_id'=>Location::where('plant_id',$e->plant_id)->first()->id,'report_date'=>'2026-10-15','shift'=>'1','meter_start'=>1000,'meter_end'=>1030,'work_start_at'=>'2026-10-15T07:00:00+07:00','work_end_at'=>'2026-10-15T10:00:00+07:00','equipment_condition'=>'WORKING','activities'=>[['activity_code_id'=>ActivityCode::where('code','237')->first()->id,'work_unit_id'=>WorkUnit::where('code','KG')->first()->id,'volume'=>50]],'fuels'=>[]]; }
 private function batch($uuid=null,$version=0){return ['client_batch_id'=>(string)Str::uuid(),'items'=>[['client_uuid'=>$uuid?:(string)Str::uuid(),'server_version'=>$version,'operation'=>'save','data'=>$this->payload()]]];}
 public function test_sync_replay_and_hash_integrity(){ $this->actingAs($this->operator());$batch=$this->batch();$a=$this->postJson('/api/v1/sync/push',$batch)->assertOk()->assertJsonPath('data.items.0.sync_status','SYNCED');$id=$a->json('data.items.0.server.id');$this->postJson('/api/v1/sync/push',$batch)->assertOk()->assertJsonPath('data.items.0.server.id',$id);$this->assertEquals(1,DailyReport::count());$batch['items'][0]['data']['meter_end']=1040;$this->postJson('/api/v1/sync/push',$batch)->assertStatus(409); }
 public function test_stale_version_and_final_status_cannot_be_overwritten(){ $this->actingAs($this->operator());$b=$this->batch();$response=$this->postJson('/api/v1/sync/push',$b)->assertOk();$server=$response->json('data.items.0.server');$this->postJson('/api/v1/sync/push',$this->batch($b['items'][0]['client_uuid'],0))->assertOk()->assertJsonPath('data.items.0.sync_status','CONFLICT');$r=DailyReport::find($server['id']);$r->update(['status'=>'SUBMITTED']);$this->postJson('/api/v1/sync/push',$this->batch($r->client_uuid,$r->server_version))->assertOk()->assertJsonPath('data.items.0.sync_status','CONFLICT');$this->assertEquals('SUBMITTED',$r->fresh()->status); }
 public function test_cursor_is_bound_to_owner_and_scope(){ $this->actingAs($this->operator());$cursor=$this->getJson('/api/v1/sync/bootstrap')->assertOk()->json('data.cursor');$this->getJson('/api/v1/sync/pull?cursor='.urlencode($cursor))->assertOk();$this->actingAs(User::where('username','supervisor')->first());$this->getJson('/api/v1/sync/pull?cursor='.urlencode($cursor))->assertStatus(422); }
 public function test_direct_api_mutation_idempotency(){ $this->actingAs($this->operator());$headers=['Idempotency-Key'=>(string)Str::uuid()];$p=$this->payload();$a=$this->postJson('/api/v1/daily-reports',$p,$headers)->assertCreated();$this->postJson('/api/v1/daily-reports',$p,$headers)->assertCreated()->assertJsonPath('data.id',$a->json('data.id'));$this->assertEquals(1,DailyReport::count()); }
 public function test_sync_uses_business_validation(){ $this->actingAs($this->operator());$b=$this->batch();$b['items'][0]['data']['meter_end']=900;$this->postJson('/api/v1/sync/push',$b)->assertOk()->assertJsonPath('data.items.0.sync_status','FAILED');$this->assertEquals(0,DailyReport::count()); }
 public function test_react_dashboard_contains_scoped_inertia_props(){ $this->actingAs($this->operator())->get('/dashboard')->assertInertia(function($page){$page->component('dashboard', false)->has('data')->has('auth.permissions');}); }

 public function test_replay_and_status_do_not_bypass_revoked_plant_scope(){
  $u=$this->operator();$this->actingAs($u);$b=$this->batch();$this->postJson('/api/v1/sync/push',$b)->assertOk();$u->plants()->detach();
  $this->postJson('/api/v1/sync/push',$b)->assertForbidden();$this->getJson('/api/v1/sync/status/'.$b['client_batch_id'])->assertForbidden();
 }
 public function test_change_journal_delivers_updates_even_when_report_timestamp_is_unchanged(){
  $this->actingAs($this->operator());$b=$this->batch();$result=$this->postJson('/api/v1/sync/push',$b)->assertOk();$report=DailyReport::find($result->json('data.items.0.server.id'));
  $cursor=$this->getJson('/api/v1/sync/bootstrap')->json('data.cursor');$cursor=$this->getJson('/api/v1/sync/pull?cursor='.urlencode($cursor))->json('data.cursor');
  $report->timestamps=false;$report->description='Updated without timestamp change';$report->save();
  $this->getJson('/api/v1/sync/pull?cursor='.urlencode($cursor))->assertOk()->assertJsonPath('data.reports.0.description','Updated without timestamp change');
 }

 public function test_iso_utc_input_is_normalized_to_business_timezone(){
  $this->actingAs($this->operator());$b=$this->batch();$b['items'][0]['data']['report_date']='2026-10-16';$b['items'][0]['data']['work_start_at']='2026-10-15T18:00:00Z';$b['items'][0]['data']['work_end_at']='2026-10-15T19:00:00Z';
  $result=$this->postJson('/api/v1/sync/push',$b)->assertOk()->assertJsonPath('data.items.0.sync_status','SYNCED');$report=DailyReport::find($result->json('data.items.0.server.id'));
  $this->assertEquals('2026-10-16 01:00',$report->work_start_at->format('Y-m-d H:i'));$this->assertEquals(60,$report->work_minutes);
 }
 public function test_sql_dashboard_preserves_fractional_hours(){
  $op=$this->operator();$this->actingAs($op);$payload=$this->payload();$payload['work_end_at']='2026-10-15T08:30:00+07:00';$service=app(\App\Services\DailyReportService::class);$report=$service->save($payload,$op);$service->transition($report,'submit',$op);$supervisor=User::where('username','supervisor')->first();$this->actingAs($supervisor);$service->transition($report,'verify',$supervisor);$service->transition($report,'approve',$supervisor);$data=app(\App\Services\DashboardService::class)->data($supervisor,[]);$this->assertEqualsWithDelta(1.5,(float)$data['trend']->first()->hours,.0001);
 }

 public function test_offline_breakdown_replay_returns_one_record(){
  $this->actingAs($this->operator());$p=$this->payload();$data=collect($p)->only('plant_id','equipment_id','location_id')->all()+['client_uuid'=>(string)Str::uuid(),'occurred_at'=>'2026-10-15T07:00:00+07:00','meter'=>1000,'symptom'=>'Mesin panas','severity'=>'HIGH','description'=>'Perlu diperiksa','file'=>\Illuminate\Http\UploadedFile::fake()->image('breakdown.jpg')];$headers=['Idempotency-Key'=>(string)Str::uuid()];
  $a=$this->postJson('/api/v1/breakdowns',$data,$headers)->assertCreated();$this->postJson('/api/v1/breakdowns',$data,$headers)->assertCreated()->assertJsonPath('data.id',$a->json('data.id'));$this->assertEquals(1,\App\Models\BreakdownReport::count());
 }
}
