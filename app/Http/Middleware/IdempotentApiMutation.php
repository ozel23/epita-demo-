<?php
namespace App\Http\Middleware;
use Closure;
use App\Models\SyncReceipt;
use Illuminate\Support\Facades\DB;
class IdempotentApiMutation
{
 public function handle($request,Closure $next){
  $key=$request->header('Idempotency-Key');abort_unless(is_string($key)&&preg_match('/^[a-zA-Z0-9_-]{16,80}$/',$key),422,'Idempotency-Key wajib (16–80 karakter).');
  $payload=$request->except('file');if($request->hasFile('file'))$payload['file_sha256']=hash_file('sha256',$request->file('file')->getRealPath());
  $hash=hash('sha256',$request->method().'|'.$request->path().'|'.json_encode($payload));
  return DB::transaction(function()use($request,$next,$key,$hash){
   $receipt=SyncReceipt::where('user_id',$request->user()->id)->where('request_key','http:'.$key)->lockForUpdate()->first();
   if($receipt){abort_unless(hash_equals($receipt->request_hash,$hash),409,'Idempotency key digunakan dengan payload berbeda.');if(isset($receipt->response['data']['plant_id']))\App\Support\PlantAccess::check($request->user(),$receipt->response['data']['plant_id']);if(isset($receipt->response['data']['daily_report_id']))\Illuminate\Support\Facades\Gate::forUser($request->user())->authorize('view',\App\Models\DailyReport::findOrFail($receipt->response['data']['daily_report_id']));return response()->json($receipt->response,$receipt->status_code);}
   $receipt=SyncReceipt::create(['user_id'=>$request->user()->id,'request_key'=>'http:'.$key,'request_hash'=>$hash]);
   $response=$next($request);
   if($response->getStatusCode()>=400){$receipt->delete();return $response;}
   $receipt->update(['response'=>json_decode($response->getContent(),true),'status_code'=>$response->getStatusCode()]);return $response;
  },3);
 }
}
