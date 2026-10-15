export const retryDelay=attempt=>Math.min(300000,1000*2**Math.min(attempt,9));
export function conflictDecision(local,server){if(!['DRAFT','RETURNED'].includes(server.status))return 'SERVER_WINS';return Number(local.server_version)===Number(server.server_version)?'SYNC':'REVIEW'}
export function nextResult(result){if(result.sync_status==='SYNCED')return 'SYNCED';if(result.sync_status==='CONFLICT')return 'CONFLICT';return 'FAILED'}
