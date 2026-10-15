import * as SQLite from 'expo-sqlite';
import * as FileSystem from 'expo-file-system/legacy';
let connection;
export const tables=['local_profile','local_settings','master_equipment','master_personnels','master_locations','master_activities','master_units','assignments_cache','daily_report_drafts','daily_report_activities','daily_report_fuels','attachment_queue','sync_queue','sync_conflicts','sync_metadata'];
export async function db(){if(!connection){connection=await SQLite.openDatabaseAsync('epita.db');await connection.execAsync('PRAGMA journal_mode=WAL; PRAGMA foreign_keys=ON;');for(const table of tables)await connection.execAsync(`CREATE TABLE IF NOT EXISTS ${table}(client_uuid TEXT PRIMARY KEY, owner_id INTEGER NOT NULL, payload TEXT NOT NULL, local_updated_at TEXT NOT NULL, server_updated_at TEXT, sync_status TEXT NOT NULL DEFAULT 'LOCAL_DRAFT', sync_attempts INTEGER NOT NULL DEFAULT 0, last_sync_error TEXT, next_attempt_at INTEGER NOT NULL DEFAULT 0);`);await connection.execAsync('PRAGMA user_version=1');await connection.runAsync("UPDATE sync_queue SET sync_status='QUEUED' WHERE sync_status='SYNCING'");await connection.runAsync("UPDATE attachment_queue SET sync_status='QUEUED' WHERE sync_status='SYNCING'");}return connection}
function safe(table){if(!tables.includes(table))throw new Error('Invalid cache table');return table}
export async function put(table,owner,id,payload,status='LOCAL_DRAFT'){const d=await db();await d.runAsync(`INSERT INTO ${safe(table)} (client_uuid,owner_id,payload,local_updated_at,sync_status) VALUES (?,?,?,?,?) ON CONFLICT(client_uuid) DO UPDATE SET payload=excluded.payload,local_updated_at=excluded.local_updated_at,sync_status=excluded.sync_status,last_sync_error=NULL`,id,owner,JSON.stringify(payload),new Date().toISOString(),status)}
export async function rows(table,owner){const d=await db();return (await d.getAllAsync(`SELECT * FROM ${safe(table)} WHERE owner_id=? ORDER BY local_updated_at DESC`,owner)).map(r=>({...r,payload:JSON.parse(r.payload)}))}
export async function remove(table,owner,id){await(await db()).runAsync(`DELETE FROM ${safe(table)} WHERE owner_id=? AND client_uuid=?`,owner,id)}
export async function clearAll(){const d=await db();await d.withExclusiveTransactionAsync(async tx=>{for(const t of tables)await tx.runAsync(`DELETE FROM ${t}`)});await d.execAsync('PRAGMA wal_checkpoint(TRUNCATE); VACUUM;');await FileSystem.deleteAsync(FileSystem.documentDirectory+'evidence/',{idempotent:true})}
export async function cacheBootstrap(owner,data){const d=await db();const mappings={master_equipment:data.equipment,master_personnels:data.master.personnels,master_locations:data.master.locations,master_activities:data.master.activities,master_units:data.master.units};await d.withExclusiveTransactionAsync(async tx=>{for(const [t,items]of Object.entries(mappings)){await tx.runAsync(`DELETE FROM ${t} WHERE owner_id=?`,owner);for(const item of items)await tx.runAsync(`INSERT INTO ${t}(client_uuid,owner_id,payload,local_updated_at,sync_status) VALUES (?,?,?,?,?)`,String(item.id),owner,JSON.stringify(item),new Date().toISOString(),'SYNCED')}});await put('local_profile',owner,'profile',data,'SYNCED')}

export async function saveDraft(owner,draft){
 const d=await db();const now=new Date().toISOString();
 await d.withExclusiveTransactionAsync(async tx=>{
  await tx.runAsync("INSERT INTO daily_report_drafts(client_uuid,owner_id,payload,local_updated_at,sync_status) VALUES (?,?,?,?,?) ON CONFLICT(client_uuid) DO UPDATE SET payload=excluded.payload,local_updated_at=excluded.local_updated_at,sync_status=excluded.sync_status",draft.client_uuid,owner,JSON.stringify(draft),now,'LOCAL_DRAFT');
  for(const [table,values]of [['daily_report_activities',draft.data.activities||[]],['daily_report_fuels',draft.data.fuels||[]]]){
   await tx.runAsync(`DELETE FROM ${table} WHERE owner_id=? AND client_uuid LIKE ?`,owner,draft.client_uuid+':%');
   for(let i=0;i<values.length;i++)await tx.runAsync(`INSERT INTO ${table}(client_uuid,owner_id,payload,local_updated_at,sync_status) VALUES (?,?,?,?,?)`,draft.client_uuid+':'+i,owner,JSON.stringify(values[i]),now,'LOCAL_DRAFT');
  }
 });
}
