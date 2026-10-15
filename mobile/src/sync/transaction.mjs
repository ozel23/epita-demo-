// Pure orchestration, independently tested. Final decisions always come from Laravel.
export async function processReport(item,{push,attachments,upload,removeAttachment}){
 const saved=await push({client_batch_id:item.client_batch_id,items:[{client_uuid:item.client_uuid,server_version:item.server_version,operation:'save',data:item.data}]});
 let result=saved.items[0];
 if(result.sync_status!=='SYNCED'||item.operation!=='submit')return result;
 for(const photo of await attachments(item.client_uuid)){await upload(result.server.id,photo);await removeAttachment(photo.client_uuid)}
 const submitted=await push({client_batch_id:item.submit_batch_id,items:[{client_uuid:item.client_uuid,server_version:result.server.server_version,operation:'submit',data:item.data}]});
 return submitted.items[0];
}
