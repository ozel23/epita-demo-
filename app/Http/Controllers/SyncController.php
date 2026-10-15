<?php
namespace App\Http\Controllers;
use App\Services\SyncService;
use App\Support\PlantAccess;
use App\Models\SyncReceipt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class SyncController extends Controller
{
 public function bootstrap(Request $r,SyncService $s){abort_unless($r->user()->can('equipment.view'),403);$master=(new ApiController())->master($r)->getData(true)['data'];return response()->json(['data'=>['profile'=>$r->user()->only('id','name','username'),'permissions'=>$r->user()->getAllPermissions()->pluck('name'),'master'=>$master,'equipment'=>PlantAccess::query(\App\Models\Equipment::where('active', true),$r->user())->get(),'cursor'=>$s->cursor($r->user()),'server_time'=>now()->toIso8601String()]]);}
 public function pull(Request $r,SyncService $s){
  $r->validate(['cursor'=>'required|string']);$p=$s->decode($r->user(),$r->cursor);
  $q=DB::table('sync_changes')->whereIn('plant_id',PlantAccess::ids($r->user()))->where('id','>', $p['change_id']??0);
  if(!$r->user()->can('daily-report.view-all'))$q->where('owner_id',$r->user()->id);
  $changes=$q->orderBy('id')->limit(101)->get();$more=$changes->count()>100;$changes=$changes->take(100);
  $rows=$s->reportQuery($r->user())->whereIn('id',$changes->pluck('daily_report_id'))->with('activities','fuels','helpers','approvals')->get();
  return response()->json(['data'=>['reports'=>$rows,'cursor'=>$s->cursor($r->user(),['change_id'=>$changes->last()->id??($p['change_id']??0)]),'has_more'=>$more,'server_time'=>now()->toIso8601String()]]);
 }
 public function push(Request $r,SyncService $s){$d=$r->validate(['client_batch_id'=>'required|uuid','items'=>'required|array|min:1|max:50','items.*.client_uuid'=>'required|uuid|distinct','items.*.server_version'=>'required|integer|min:0','items.*.operation'=>'required|in:save,submit','items.*.data'=>'required|array']);return response()->json(['data'=>$s->push($r->user(),$d)]);}
 public function acknowledge(Request $r,SyncService $s){$d=$r->validate(['device_id'=>'required|string|max:100','cursor'=>'required|string']);$s->decode($r->user(),$d['cursor']);DB::table('sync_acknowledgements')->updateOrInsert(['user_id'=>$r->user()->id,'device_id'=>$d['device_id']],['cursor'=>$d['cursor'],'updated_at'=>now(),'created_at'=>now()]);return response()->json(['data'=>['acknowledged'=>true]]);}
 public function status(Request $r,$batch){$receipt=SyncReceipt::where('user_id',$r->user()->id)->where('request_key','batch:'.$batch)->firstOrFail();foreach($receipt->response['items']??[] as $item){if(isset($item['server']['id']))\Illuminate\Support\Facades\Gate::forUser($r->user())->authorize('view',\App\Models\DailyReport::findOrFail($item['server']['id']));}return response()->json(['data'=>$receipt->response]);}
}
