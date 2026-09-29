<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditAndSummaryReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_punch_edit_writes_audit_log(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $device = Device::create([
            'name' => 'Manual Entry',
            'ip_address' => '0.0.0.0',
            'port' => 0,
            'is_active' => false,
        ]);

        $this->actingAs($admin)
            ->post(route('reports.punches.store'), [
                'employee_code' => '1001',
                'punched_at' => now()->format('Y-m-d H:i:s'),
                'status' => 'in',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'punch.created',
            'user_id' => $admin->id,
        ]);
        $this->assertDatabaseHas('attendance_logs', [
            'employee_code' => '1001',
            'device_id' => $device->id,
        ]);
    }

    public function test_summary_report_page_loads(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(route('reports.summary'))
            ->assertOk()
            ->assertSee('Summary reports');
    }

    public function test_summary_tab_data_loads_via_ajax(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->getJson(route('reports.summary.data', [
                'from' => now()->startOfMonth()->toDateString(),
                'to' => now()->toDateString(),
                'tab' => 'late',
            ]))
            ->assertOk()
            ->assertJsonStructure(['from', 'to', 'tab', 'rows', 'total']);
    }

    public function test_audit_page_requires_permission(): void
    {
        $manager = User::factory()->create(['role' => User::ROLE_MANAGER]);

        $this->actingAs($manager)
            ->get(route('audit.index'))
            ->assertForbidden();

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        app(AuditLogger::class); // ensure class loads
        \App\Models\AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'test.action',
            'summary' => 'Hello audit',
        ]);

        $this->actingAs($admin)
            ->get(route('audit.index'))
            ->assertOk()
            ->assertSee('Hello audit');
    }
}
