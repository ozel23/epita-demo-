<?php
namespace App\Services;
use App\Models\DailyReport;
use App\Models\SyncReceipt;
use App\Http\Requests\DailyReportRequest;
use App\Support\PlantAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;
class SyncService
{
 public function reportQuery($user){$q=DailyReport::whereIn('plant_id',PlantAccess::ids($user));if(!$user->can('daily-report.view-all'))$q->where('created_by',$user->id);return $q;}
 public function cursor($user,array $position=[]){return Crypt::encryptString(json_encode(['user_id'=>$user->id,'security_version'=>$user->security_version,'plants'=>array_map('intval',PlantAccess::ids($user)),'expires'=>time()+604800,'position'=>$position]));}
 public function decode($user,$cursor){try{$c=json_decode(Crypt::decryptString($cursor),true);}catch(\Throwable $e){abort(422,'Cursor tidak valid; bootstrap ulang.');}abort_unless($c['user_id']===$user->id&&$c['security_version']===$user->security_version&&$c['plants']===array_map('intval',PlantAccess::ids($user))&&$c['expires']>=time(),422,'Cursor kedaluwarsa atau cakupan berubah; bootstrap ulang.');return $c['position'];}
 public function push($user,array $batch){
  $key='batch:'.$batch['client_batch_id'];$hash=hash('sha256',json_encode($batch));
  return DB::transaction(function()use($user,$batch,$key,$hash){
   $receipt=SyncReceipt::where('user_id',$user->id)->where('request_key',$key)->lockForUpdate()->first();
   if($receipt){abort_unless(hash_equals($receipt->request_hash,$hash),409,'Idempotency key digunakan dengan data berbeda.');foreach($receipt->response['items']??[] as $result){if(isset($result['server']['id']))Gate::forUser($user)->authorize('view',DailyReport::findOrFail($result['server']['id']));}return $receipt->response;}
   $receipt=SyncReceipt::create(['user_id'=>$user->id,'request_key'=>$key,'request_hash'=>$hash]);$results=[];
   foreach($batch['items'] as $item){
    try{$results[]=DB::transaction(function()use($user,$item){
     $report=DailyReport::where('client_uuid',$item['client_uuid'])->lockForUpdate()->first();
     if($report){Gate::forUser($user)->authorize('view',$report);if((int)$item['server_version']!==(int)$report->server_version||!in_array($report->status,['DRAFT','RETURNED']))return ['client_uuid'=>$item['client_uuid'],'sync_status'=>'CONFLICT','server'=>$report->load('activities','fuels','helpers','approvals')];Gate::forUser($user)->authorize('update',$report);}
     else{abort_unless($user->can('daily-report.create'),403);abort_unless((int)$item['server_version']===0,409,'Versi server tidak ditemukan.');}
     $rules=(new DailyReportRequest())->rules();$data=Validator::make($item['data'],$rules)->validate();
     $report=app(DailyReportService::class)->save($data,$user,$report);
     if($report->client_uuid!==$item['client_uuid']){$report->client_uuid=$item['client_uuid'];$report->save();}
     if($item['operation']==='submit')$report=app(DailyReportService::class)->transition($report,'submit',$user);
     AuditService::record('sync.accepted',$report,[],['client_uuid'=>$item['client_uuid'],'operation'=>$item['operation'],'server_version'=>$report->server_version]);
     return ['client_uuid'=>$item['client_uuid'],'sync_status'=>'SYNCED','server'=>$report->fresh()->load('activities','fuels','helpers','approvals')];
    });}catch(\Illuminate\Database\Eloquent\ModelNotFoundException $e){$results[]=['client_uuid'=>$item['client_uuid'],'sync_status'=>'FAILED','status'=>404,'message'=>'Referensi data tidak tersedia pada cakupan ini.'];}catch(\Illuminate\Auth\Access\AuthorizationException $e){$results[]=['client_uuid'=>$item['client_uuid'],'sync_status'=>'FAILED','status'=>403,'message'=>'Akses laporan tidak diizinkan.'];}catch(ValidationException $e){$results[]=['client_uuid'=>$item['client_uuid'],'sync_status'=>'FAILED','errors'=>$e->errors()];}catch(\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e){$results[]=['client_uuid'=>$item['client_uuid'],'sync_status'=>'FAILED','status'=>$e->getStatusCode(),'message'=>$e->getMessage()?:'Akses atau status tidak diizinkan.'];}
   }
   $response=['client_batch_id'=>$batch['client_batch_id'],'items'=>$results];$receipt->update(['response'=>$response]);return $response;
  },3);
 }
}
