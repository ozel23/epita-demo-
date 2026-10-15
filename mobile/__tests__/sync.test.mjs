import test from 'node:test';
import assert from 'node:assert/strict';
import {retryDelay,conflictDecision,nextResult} from '../src/sync/policy.mjs';
test('retry grows but is bounded',()=>{assert.equal(retryDelay(0),1000);assert.equal(retryDelay(3),8000);assert.equal(retryDelay(100),300000)});
test('approval always wins over local draft',()=>{for(const status of ['SUBMITTED','VERIFIED','APPROVED'])assert.equal(conflictDecision({server_version:2},{status,server_version:2}),'SERVER_WINS')});
test('draft versions require review on conflict',()=>{assert.equal(conflictDecision({server_version:1},{status:'DRAFT',server_version:2}),'REVIEW');assert.equal(conflictDecision({server_version:2},{status:'RETURNED',server_version:2}),'SYNC')});
test('failed operation never appears synced',()=>{assert.equal(nextResult({sync_status:'CONFLICT'}),'CONFLICT');assert.equal(nextResult({sync_status:'FAILED'}),'FAILED')});
