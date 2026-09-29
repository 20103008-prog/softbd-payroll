<?php

namespace Tests\Feature;

use App\Models\AttendanceLog;
use App\Models\Device;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ManualPunchAndLiveSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_punch_toggles_in_then_out_on_manual_entry_device(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $employee = Employee::query()->create([
            'name' => 'Demo Worker',
            'employee_code' => 'D100',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->post(route('employees.punch', $employee))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('attendance_logs', [
            'employee_code' => 'D100',
            'status' => 'in',
        ]);

        $manual = Device::query()->where('ip_address', '0.0.0.0')->where('port', 0)->first();
        $this->assertNotNull($manual);
        $this->assertSame('Manual Entry', $manual->name);
        $this->assertFalse($manual->is_active);

        $this->actingAs($admin)
            ->post(route('employees.punch', $employee))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('attendance_logs', [
            'employee_code' => 'D100',
            'status' => 'out',
            'device_id' => $manual->id,
        ]);

        $this->assertSame(2, AttendanceLog::query()->where('employee_code', 'D100')->count());
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'punch.created',
            'user_id' => $admin->id,
        ]);
    }

    public function test_portal_user_can_check_in_from_my_attendance(): void
    {
        $employee = Employee::query()->create([
            'name' => 'Sarder',
            'employee_code' => '1696',
            'status' => 'active',
        ]);
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'employee_id' => $employee->id,
        ]);

        $this->actingAs($user)
            ->post(route('me.attendance.punch'))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('attendance_logs', [
            'employee_code' => '1696',
            'status' => 'in',
        ]);
    }

    public function test_sync_now_is_blocked_when_live_sync_is_off(): void
    {
        Config::set('zkteco.live_sync', false);

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $device = Device::query()->create([
            'name' => 'Main-Gate',
            'ip_address' => '192.168.68.201',
            'port' => 4370,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('devices.sync', $device))
            ->assertRedirect(route('devices.index'))
            ->assertSessionHas('warning');

        $this->assertSame(0, AttendanceLog::query()->count());
    }
}
