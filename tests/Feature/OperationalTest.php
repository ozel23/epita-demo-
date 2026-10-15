<?php

namespace Tests\Feature;

use App\Models\ActivityCode;
use App\Models\BackgroundTask;
use App\Models\Equipment;
use App\Models\Location;
use App\Models\Personnel;
use App\Models\Plant;
use App\Models\SapImportBatch;
use App\Models\User;
use App\Models\WorkUnit;
use App\Services\AssignmentService;
use App\Services\DailyReportService;
use App\Services\DashboardService;
use App\Services\MaintenanceService;
use App\Services\ReportService;
use App\Services\SapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
class OperationalTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }
    private function user($role = 'operator')
    {
        return User::where('username', $role)->firstOrFail();
    }
    private function payload(array $overrides = [])
    {
        $e = Equipment::where('meter_type', 'KM')->first();
        return array_replace(['plant_id' => $e->plant_id, 'equipment_id' => $e->id, 'main_operator_id' => Personnel::where('type', 'OPERATOR')->first()->id, 'location_id' => Location::where('plant_id', $e->plant_id)->first()->id, 'report_date' => '2026-10-10', 'shift' => '1', 'meter_start' => 1000, 'meter_end' => 1039, 'work_start_at' => '2026-10-10 07:00:00', 'work_end_at' => '2026-10-10 16:00:00', 'equipment_condition' => 'WORKING', 'activities' => [['activity_code_id' => ActivityCode::where('code', '237')->first()->id, 'work_unit_id' => WorkUnit::where('code', 'KG')->first()->id, 'volume' => 100]], 'fuels' => [['fuel_type_id' => \App\Models\FuelType::where('code', 'SOLAR')->firstOrFail()->id, 'quantity_filled' => 20, 'quantity_used' => 15, 'quantity_remaining' => 5]]], $overrides);
    }
    private function report(array $overrides = [])
    {
        $this->actingAs($u = $this->user());
        return app(DailyReportService::class)->save($this->payload($overrides), $u);
    }
    private function approved(array $overrides = [])
    {
        $r = $this->report($overrides);
        $s = app(DailyReportService::class);
        $s->transition($r, 'submit', $this->user());
        $this->actingAs($u = $this->user('supervisor'));
        $s->transition($r, 'verify', $u);
        return $s->transition($r, 'approve', $u);
    }
    public function test_login_redirect_and_inactive_rejected()
    {
        foreach (['operator', 'supervisor', 'admin', 'maintenance', 'manager'] as $role) {
            $this->post('/login', ['login' => $role, 'password' => 'DemoVra!2026'])->assertRedirect('/dashboard');
            $this->post('/logout');
        }
        $this->user()->update(['active' => false]);
        $this->post('/login', ['login' => 'operator', 'password' => 'DemoVra!2026'])->assertSessionHasErrors('login');
        $this->assertGuest();
    }
    public function test_calculation_and_cross_midnight()
    {
        $r = $this->report(['work_start_at' => '2026-10-10 22:00', 'work_end_at' => '2026-10-11 02:30']);
        $this->assertEquals(39, $r->meter_total);
        $this->assertEquals(270, $r->work_minutes);
        $this->assertEqualsWithDelta(15 / 39, $r->fuels->first()->ratio, 0.0001);
    }
    public function test_invalid_meter_rejected()
    {
        $this->actingAs($this->user())->postJson('/api/v1/daily-reports', $this->payload(['meter_end' => 900]), ['Idempotency-Key' => 'invalid-meter-test-2026'])->assertStatus(422)->assertJsonValidationErrors('meter_end');
    }
    public function test_plant_scope_blocks_cross_plant()
    {
        $r = $this->report();
        $u = $this->user('supervisor');
        $u->plants()->sync([Plant::where('code', 'BS12')->first()->id]);
        $this->actingAs($u)->get('/daily-reports/' . $r->id)->assertForbidden();
        $this->getJson('/api/v1/equipment')->assertOk()->assertJsonMissing(['code' => $r->equipment->code]);
    }
    public function test_master_permissions_and_crud()
    {
        $this->actingAs($this->user())->post('/master/load-types', ['code' => 'NEW', 'name' => 'New', 'active' => 1])->assertForbidden();
        $this->actingAs($this->user('admin'))->post('/master/load-types', ['code' => 'NEW', 'name' => 'Muatan Baru', 'active' => 1])->assertRedirect('/master/load-types');
        $this->assertDatabaseHas('load_types', ['code' => 'NEW']);
    }
    public function test_assignment_conflict_is_rejected()
    {
        $u = $this->user('supervisor');
        $this->actingAs($u);
        $d = collect($this->payload())->only('plant_id', 'equipment_id')->all() + ['operator_id' => Personnel::where('type', 'OPERATOR')->first()->id, 'planned_start_at' => '2026-10-10 07:00', 'planned_end_at' => '2026-10-10 16:00', 'shift' => '1'];
        app(AssignmentService::class)->create($d, $u);
        $this->expectException(ValidationException::class);
        app(AssignmentService::class)->create($d, $u);
    }
    public function test_return_revise_verify_approve_and_freeze()
    {
        $r = $this->report();
        $s = app(DailyReportService::class);
        $s->transition($r, 'submit', $this->user());
        $u = $this->user('supervisor');
        $this->actingAs($u);
        $s->transition($r, 'return', $u, 'Perbaiki uraian');
        $this->actingAs($op = $this->user());
        $r = $s->save($this->payload(['description' => 'Diperbaiki']), $op, $r);
        $s->transition($r, 'submit', $op);
        $this->actingAs($u);
        $s->transition($r, 'verify', $u);
        $r = $s->transition($r, 'approve', $u);
        $this->assertEquals('APPROVED', $r->status);
        $this->assertEquals(1039, $r->equipment->last_meter);
        $this->assertCount(5, $r->approvals);
        $this->actingAs($op)->putJson('/daily-reports/' . $r->id, $this->payload())->assertForbidden();
    }
    public function test_operator_cannot_approve()
    {
        $r = $this->report();
        $this->postJson('/daily-reports/' . $r->id . '/approve')->assertStatus(422);
        $r->update(['status' => 'VERIFIED']);
        $this->postJson('/daily-reports/' . $r->id . '/approve')->assertForbidden();
    }
    public function test_double_approval_is_rejected()
    {
        $r = $this->approved();
        $this->postJson('/daily-reports/' . $r->id . '/approve')->assertStatus(422);
    }
    public function test_breakdown_maintenance_release()
    {
        $u = $this->user('maintenance');
        $this->actingAs($u);
        $d = $this->payload();
        $s = app(MaintenanceService::class);
        $b = $s->breakdown(['plant_id' => $d['plant_id'], 'equipment_id' => $d['equipment_id'], 'location_id' => $d['location_id'], 'occurred_at' => '2026-10-10 08:00', 'meter' => 1000, 'symptom' => 'Mesin panas', 'severity' => 'HIGH', 'description' => 'Overheat'], $u);
        $this->assertEquals('BREAKDOWN', $b->equipment->current_condition);
        $wo = $s->create(['plant_id' => $b->plant_id, 'equipment_id' => $b->equipment_id, 'breakdown_report_id' => $b->id, 'type' => 'CORRECTIVE', 'priority' => 'HIGH', 'technicians' => [Personnel::where('type', 'MECHANIC')->first()->id]], $u);
        $s->transition($wo, 'start', [], $u);
        $this->assertEquals('MAINTENANCE', $wo->equipment->fresh()->current_condition);
        $s->action($wo, ['technician_id' => Personnel::where('type', 'MECHANIC')->first()->id, 'description' => 'Ganti filter', 'labor_minutes' => 60], $u);
        $s->transition($wo, 'complete', ['diagnosis' => 'Filter tersumbat', 'result' => 'Testing normal'], $u);
        $s->transition($wo, 'close', ['condition' => 'STANDBY'], $u);
        $this->assertEquals('STANDBY', $wo->equipment->fresh()->current_condition);
        $this->assertEquals('CLOSED', $b->fresh()->status);
        $this->assertDatabaseCount('equipment_status_histories', 3);
        $this->get('/maintenance/work-orders/' . $wo->id)->assertOk()->assertInertia(function ($page) { $page->component('operations.order', false)->has('history'); });
    }
    private function sapRow($r, $confirmation = '162280', $value = 39)
    {
        return ['equipment_code' => $r->equipment->code, 'plant_code' => 'BS01', 'order_number' => 'BS01MTB00079', 'operation_activity' => '0010', 'confirmation_number' => $confirmation, 'confirmation_counter' => '1', 'posting_date' => '10.10.2026', 'actual_work' => $value, 'actual_work_unit' => 'KM'];
    }
    public function test_sap_staging_errors_duplicates_and_raw_preserved()
    {
        $r = $this->report();
        $u = $this->user('admin');
        $this->actingAs($u);
        $row = $this->sapRow($r);
        $b = app(SapService::class)->stage([$row, $row, array_replace($row, ['confirmation_number' => '2', 'posting_date' => '31.02.2026'])], $r->plant_id, 'test.csv', $u);
        $this->assertEquals(2, $b->error_count);
        $this->assertEquals('DUPLICATE', $b->rows[1]->status);
        $this->assertSame('10.10.2026', $b->rows[0]->raw_data['posting_date']);
        $this->expectException(ValidationException::class);
        app(SapService::class)->commit($b, $u);
    }
    public function test_sap_reconciliation_matched_mismatch_only_local_and_only_sap()
    {
        $r = $this->approved();
        $other = Equipment::where('plant_id', $r->plant_id)->where('meter_type', 'KM')->where('id', '!=', $r->equipment_id)->first();
        $second = $this->approved(['equipment_id' => $other->id, 'meter_start' => 1000, 'meter_end' => 1020]);
        $u = $this->user('admin');
        $this->actingAs($u);
        $s = app(SapService::class);
        $b = $s->stage([$this->sapRow($r), array_replace($this->sapRow($r, '162281'), ['equipment_code' => Equipment::where('plant_id', $r->plant_id)->where('meter_type', 'HM')->first()->code])], $r->plant_id, 'test.csv', $u);
        $s->commit($b, $u);
        $statuses = $s->reconcile($b, $u)->pluck('status')->all();
        $this->assertContains('MATCHED', $statuses);
        $this->assertContains('ONLY_LOCAL', $statuses);
        $this->assertContains('ONLY_SAP', $statuses);
        $b2 = $s->stage([$this->sapRow($r, '162282', 50)], $r->plant_id, 'test2.csv', $u);
        $s->commit($b2, $u);
        $this->assertContains('VALUE_MISMATCH', $s->reconcile($b2, $u)->pluck('status')->all());
        $s->rollback($b2, $u);
        $this->assertEquals('ROLLED_BACK', $b2->fresh()->status);
        $this->assertCount(1, $b2->rows);
    }
    public function test_export_filters_and_dashboard_real_values()
    {
        $r = $this->approved();
        $u = $this->user('admin');
        $this->actingAs($u);
        $rows = app(ReportService::class)->rows('daily', $u, ['from' => '2026-10-11']);
        $this->assertCount(0, $rows);
        $data = app(DashboardService::class)->data($u, []);
        $this->assertEquals(39, $data['km']);
        $this->assertEquals(15, $data['fuel']);
        $this->assertEquals(9, $data['work_hours']);
        $this->get('/reports/daily?format=csv')->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->get('/reports/daily?format=pdf')->assertOk()->assertHeader('content-type', 'application/pdf');
    }
    public function test_all_pages_render_for_authorized_roles()
    {
        $pages = ['operator' => ['/dashboard', '/daily-reports', '/daily-reports/create', '/assignments', '/breakdowns', '/profile', '/notifications', '/select-plant'], 'admin' => ['/dashboard', '/master/equipment', '/master/equipment/create', '/users', '/roles', '/sap/imports', '/sap/reconciliation', '/reports', '/audit-logs'], 'supervisor' => ['/work-plans', '/assignments', '/approvals'], 'maintenance' => ['/maintenance/work-orders', '/breakdowns']];
        foreach ($pages as $role => $urls) {
            $this->actingAs($this->user($role));
            foreach ($urls as $url) {
                $this->get($url)->assertOk();
            }
        }
    }
    public function test_api_login_and_scoped_read()
    {
        $token = $this->postJson('/api/v1/login', ['login' => 'operator', 'password' => 'DemoVra!2026', 'device_name' => 'test'])->assertOk()->json('data.token');
        $this->withHeader('Authorization', 'Bearer ' . $token)->getJson('/api/v1/me')->assertOk()->assertJsonPath('data.username', 'operator');
    }
    public function test_approved_revision_preserves_snapshot_and_recalculates_meter()
    {
        $r = $this->approved();
        $u = $this->user('supervisor');
        $s = app(DailyReportService::class);
        $s->transition($r, 'revise', $u, 'Koreksi meter akhir');
        $this->assertEquals(1000, $r->equipment->fresh()->last_meter);
        $this->actingAs($op = $this->user());
        $r = $s->save($this->payload(['meter_end' => 1045]), $op, $r);
        $s->transition($r, 'submit', $op);
        $this->actingAs($u);
        $s->transition($r, 'verify', $u);
        $r = $s->transition($r, 'approve', $u);
        $this->assertEquals(1, $r->revision_no);
        $this->assertEquals(1045, $r->equipment->last_meter);
        $this->assertEquals(1039, $r->approvals()->where('to_status', 'APPROVED')->first()->snapshot['meter_end']);
    }
    public function test_return_requires_note()
    {
        $r = $this->report();
        app(DailyReportService::class)->transition($r, 'submit', $this->user());
        $this->actingAs($this->user('supervisor'))->postJson('/daily-reports/' . $r->id . '/return', [])->assertStatus(422)->assertJsonValidationErrors('note');
    }
    public function test_self_approval_is_forbidden_even_with_permission()
    {
        $r = $this->report();
        $r->update(['status' => 'VERIFIED']);
        $u = $this->user();
        $u->givePermissionTo(['daily-report.approve', 'daily-report.view-all']);
        $this->actingAs($u)->postJson('/daily-reports/' . $r->id . '/approve')->assertForbidden();
    }
    public function test_meter_override_requires_permission_and_reason()
    {
        $this->actingAs($this->user())->postJson('/daily-reports', $this->payload(['meter_start' => 999]))->assertStatus(422);
        $this->actingAs($this->user('supervisor'))->postJson('/daily-reports', $this->payload(['meter_start' => 999, 'meter_override_reason' => 'Meter diganti dengan bukti pemeriksaan']))->assertCreated();
        $this->assertDatabaseHas('audit_logs', ['event' => 'meter.override']);
    }
    public function test_duplicate_report_interval_rejected()
    {
        $this->report();
        $this->postJson('/daily-reports', $this->payload())->assertStatus(422)->assertJsonValidationErrors('equipment_id');
    }
    public function test_photo_required_and_private_attachment_scoped()
    {
        Storage::fake('local');
        $r = $this->report(['equipment_condition' => 'BREAKDOWN']);
        $this->postJson('/daily-reports/' . $r->id . '/submit')->assertStatus(422)->assertJsonValidationErrors('attachments');
        $this->postJson('/daily-reports/' . $r->id . '/attachments', ['file' => UploadedFile::fake()->image('bukti.jpg', 100, 80)])->assertCreated();
        $a = $r->attachments()->first();
        $this->assertNotNull($a->thumbnail_path);
        $this->get('/attachments/' . $a->id)->assertOk();
        $u = $this->user('supervisor');
        $u->plants()->sync([Plant::where('code', 'BS12')->firstOrFail()->id]);
        $this->actingAs($u)->get('/attachments/' . $a->id)->assertForbidden();
    }
    public function test_upload_rejects_executable_disguised_as_image()
    {
        $r = $this->report();
        $this->postJson('/daily-reports/' . $r->id . '/attachments', ['file' => UploadedFile::fake()->createWithContent('evil.jpg', '<?php echo 1;')])->assertStatus(422);
    }
    public function test_admin_does_not_have_approval_permission()
    {
        $r = $this->report();
        $r->update(['status' => 'VERIFIED']);
        $this->actingAs($this->user('admin'))->postJson('/daily-reports/' . $r->id . '/approve')->assertForbidden();
    }
    public function test_queue_export_scopes_and_private_download()
    {
        Storage::fake('local');
        $r = $this->approved();
        $this->actingAs($this->user('admin'))->post('/background-tasks/export', ['report_type' => 'daily', 'plant_id' => $r->plant_id])->assertRedirect('/background-tasks');
        $task = BackgroundTask::first();
        $this->assertEquals('COMPLETED', $task->status);
        $this->get('/background-tasks/' . $task->id . '/download')->assertOk();
        $this->actingAs($this->user('manager'))->get('/background-tasks/' . $task->id . '/download')->assertForbidden();
    }
    public function test_sap_http_csv_import_and_template()
    {
        $this->actingAs($this->user('admin'))->get('/sap/template')->assertOk();
        $equipment = Equipment::where('plant_id', Plant::where('code', 'BS01')->firstOrFail()->id)->first();
        $csv = "equipment_code,plant_code,confirmation_number,confirmation_counter,posting_date,actual_work,actual_work_unit\n" . $equipment->code . ",BS01,162289,1,10.10.2026,39,KM\n";
        $this->post('/sap/imports', ['plant_id' => Plant::where('code', 'BS01')->firstOrFail()->id, 'file' => UploadedFile::fake()->createWithContent('iw47.csv', $csv)])->assertRedirect();
        $this->assertDatabaseHas('sap_import_batches', ['row_count' => 1, 'error_count' => 0, 'status' => 'STAGED']);
        $batch = SapImportBatch::first();
        $this->get('/sap/imports/' . $batch->id)->assertOk();
    }
    public function test_reconciliation_date_and_mapping_exceptions()
    {
        $r = $this->approved();
        $u = $this->user('admin');
        $this->actingAs($u);
        $s = app(SapService::class);
        $row = $this->sapRow($r);
        $b = $s->stage([array_replace($row, ['posting_date' => '11.10.2026'])], $r->plant_id, 'date.csv', $u);
        $s->commit($b, $u);
        $this->assertContains('DATE_MISMATCH', $s->reconcile($b, $u)->pluck('status')->all());
        $b = $s->stage([array_replace($row, ['confirmation_number' => '162299', 'order_number' => 'UNKNOWN'])], $r->plant_id, 'mapping.csv', $u);
        $s->commit($b, $u);
        $this->assertContains('MAPPING_MISSING', $s->reconcile($b, $u)->pluck('status')->all());
    }
    public function test_dashboard_date_filter_and_export_plant_filter()
    {
        $r = $this->approved();
        $u = $this->user('admin');
        $this->actingAs($u);
        $this->assertEquals(0, app(DashboardService::class)->data($u, ['from' => '2026-10-11'])['km']);
        $this->assertCount(0, app(ReportService::class)->rows('daily', $u, ['plant_id' => Plant::where('code', 'BS12')->firstOrFail()->id]));
        $this->get('/reports/daily?format=xlsx')->assertOk();
    }
    public function test_overdue_notifications_are_not_duplicated()
    {
        $this->actingAs($u = $this->user('supervisor'));
        $d = $this->payload();
        app(AssignmentService::class)->create(['plant_id' => $d['plant_id'], 'equipment_id' => $d['equipment_id'], 'operator_id' => $d['main_operator_id'], 'planned_start_at' => now()->subDays(2), 'planned_end_at' => now()->subDay(), 'shift' => '1'], $u);
        $this->artisan('operations:notify-overdue')->assertExitCode(0);
        $count = DB::table('notifications')->count();
        $this->artisan('operations:notify-overdue')->assertExitCode(0);
        $this->assertEquals($count, DB::table('notifications')->count());
    }
    public function test_role_assignment_and_user_scope()
    {
        $this->actingAs($this->user('admin'))->post('/users', ['name' => 'Operator Baru', 'username' => 'baru', 'email' => 'baru@vra.test', 'password' => 'LongSecureDemo!2026', 'role' => 'operator', 'plants' => [Plant::where('code', 'BS01')->firstOrFail()->id]])->assertRedirect();
        $this->assertTrue(User::where('username', 'baru')->firstOrFail()->hasRole('operator'));
        $u = $this->user('admin');
        $u->plants()->sync([Plant::where('code', 'BS01')->firstOrFail()->id]);
        $this->actingAs($u)->post('/users', ['name' => 'Luar', 'username' => 'luar', 'email' => 'luar@vra.test', 'password' => 'LongSecureDemo!2026', 'role' => 'operator', 'plants' => [Plant::where('code', 'BS12')->firstOrFail()->id]])->assertForbidden();
    }
    public function test_soft_deleted_master_retains_report_history()
    {
        $r = $this->approved();
        $this->actingAs($this->user('admin'))->delete('/master/equipment/' . $r->equipment_id)->assertRedirect();
        $this->actingAs($this->user('supervisor'))->get('/daily-reports/' . $r->id)->assertOk();
    }
    public function test_report_date_serializes_without_timezone_shift()
    {
        $r = $this->report();
        $this->assertSame('2026-10-10', $r->toArray()['report_date']);
        $this->assertSame('2026-10-10', app(DashboardService::class)->data($this->user('admin'))['recent']->first()->toArray()['report_date']);
    }
    public function test_all_report_types_respect_explicit_plant_filter()
    {
        $this->approved();
        $this->actingAs($u = $this->user('admin'));
        foreach (array_keys(ReportService::TYPES) as $type) {
            $this->assertCount(0, app(ReportService::class)->rows($type, $u, ['plant_id' => Plant::where('code', 'BS12')->firstOrFail()->id]), $type);
        }
        $this->get('/reports/daily?to=2026-10-14')->assertOk();
        $this->get('/dashboard?to=2026-10-14')->assertOk();
    }
    public function test_dashboard_comparisons_use_previous_period_data()
    {
        $this->approved();
        $this->approved(['report_date' => '2026-10-11', 'work_start_at' => '2026-10-11 07:00:00', 'work_end_at' => '2026-10-11 11:00:00', 'meter_start' => 1039, 'meter_end' => 1059, 'fuels' => [['fuel_type_id' => \App\Models\FuelType::where('code', 'SOLAR')->firstOrFail()->id, 'quantity_filled' => 10, 'quantity_used' => 10, 'quantity_remaining' => 0]]]);
        $user = $this->user('admin');
        $this->actingAs($user);
        $data = app(DashboardService::class)->data($user, ['from' => '2026-10-11', 'to' => '2026-10-11']);
        $this->assertSame(-55.6, $data['comparisons']['work_hours']);
        $this->assertSame(-33.3, $data['comparisons']['fuel']);
        $this->assertSame(16.7, $data['utilization_average']);
        $empty = app(DashboardService::class)->data($user, ['plant_id' => Plant::where('code', 'BS12')->firstOrFail()->id]);
        $this->assertNull($empty['comparisons']['fuel']);
    }
}
