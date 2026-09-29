<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Notifications\LeaveSubmittedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ManagerScopeAndLeaveNotifyTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_only_sees_own_department_leave(): void
    {
        $deptA = Department::create(['name' => 'Sales']);
        $deptB = Department::create(['name' => 'Ops']);

        $managerEmp = Employee::create([
            'name' => 'Manager Emp',
            'employee_code' => 'M1',
            'department_id' => $deptA->id,
            'status' => 'active',
        ]);
        $empA = Employee::create([
            'name' => 'In Team',
            'employee_code' => 'A1',
            'department_id' => $deptA->id,
            'status' => 'active',
        ]);
        $empB = Employee::create([
            'name' => 'Other Dept',
            'employee_code' => 'B1',
            'department_id' => $deptB->id,
            'status' => 'active',
        ]);

        $type = LeaveType::create(['name' => 'Annual', 'annual_quota' => 14, 'is_paid' => true]);

        $leaveA = LeaveRequest::create([
            'employee_id' => $empA->id,
            'leave_type_id' => $type->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'days' => 1,
            'status' => LeaveRequest::STATUS_PENDING,
        ]);
        $leaveB = LeaveRequest::create([
            'employee_id' => $empB->id,
            'leave_type_id' => $type->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'days' => 1,
            'status' => LeaveRequest::STATUS_PENDING,
        ]);

        $manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
            'employee_id' => $managerEmp->id,
        ]);

        $response = $this->actingAs($manager)->get(route('leave.index'));
        $response->assertOk();
        $response->assertSee('In Team');
        $response->assertDontSee('Other Dept');

        $this->actingAs($manager)
            ->post(route('leave.approve', $leaveB))
            ->assertForbidden();

        $this->actingAs($manager)
            ->post(route('leave.approve', $leaveA))
            ->assertRedirect();

        $this->assertSame(LeaveRequest::STATUS_APPROVED, $leaveA->fresh()->status);
    }

    public function test_leave_submit_notifies_approvers(): void
    {
        Notification::fake();

        $dept = Department::create(['name' => 'HR Dept']);
        $employee = Employee::create([
            'name' => 'Worker',
            'employee_code' => 'W1',
            'department_id' => $dept->id,
            'status' => 'active',
            'email' => 'worker@example.com',
        ]);
        $type = LeaveType::create(['name' => 'Casual', 'annual_quota' => 10, 'is_paid' => true]);

        $hr = User::factory()->create(['role' => User::ROLE_HR]);
        $portalUser = User::factory()->create([
            'role' => User::ROLE_USER,
            'employee_id' => $employee->id,
        ]);

        $monday = now()->next('Monday');

        $this->actingAs($portalUser)
            ->post(route('me.leave.store'), [
                'leave_type_id' => $type->id,
                'start_date' => $monday->toDateString(),
                'end_date' => $monday->toDateString(),
                'reason' => 'Personal',
            ])
            ->assertRedirect(route('me.leave'));

        Notification::assertSentTo($hr, LeaveSubmittedNotification::class);
    }

    public function test_add_leave_form_preselects_employee_from_query(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_HR]);
        $employee = Employee::create([
            'name' => 'Preselect Emp',
            'employee_code' => 'PS1',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get(route('leave.create', ['employee_id' => $employee->id]))
            ->assertOk()
            ->assertSee('value="'.$employee->id.'" selected', false);
    }

    public function test_cannot_submit_leave_for_past_or_today_dates(): void
    {
        $employee = Employee::create([
            'name' => 'Future Only Worker',
            'employee_code' => 'FW1',
            'status' => 'active',
        ]);
        $type = LeaveType::create(['name' => 'Medical', 'annual_quota' => 14, 'is_paid' => true]);

        $portalUser = User::factory()->create([
            'role' => User::ROLE_USER,
            'employee_id' => $employee->id,
        ]);

        // Attempt today's date
        $this->actingAs($portalUser)
            ->post(route('me.leave.store'), [
                'leave_type_id' => $type->id,
                'start_date' => now()->toDateString(),
                'end_date' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('start_date');

        // Attempt past date (yesterday)
        $this->actingAs($portalUser)
            ->post(route('me.leave.store'), [
                'leave_type_id' => $type->id,
                'start_date' => now()->subDay()->toDateString(),
                'end_date' => now()->subDay()->toDateString(),
            ])
            ->assertSessionHasErrors('start_date');

        // Attempt future working day (e.g. next Monday)
        $futureWorkingDay = now()->next('Monday');
        $this->actingAs($portalUser)
            ->post(route('me.leave.store'), [
                'leave_type_id' => $type->id,
                'start_date' => $futureWorkingDay->toDateString(),
                'end_date' => $futureWorkingDay->toDateString(),
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('me.leave'));
    }

    public function test_manager_and_hr_cannot_approve_own_leave_request(): void
    {
        $dept = Department::create(['name' => 'IT Dept']);

        $managerEmp = Employee::create([
            'name' => 'IT Manager',
            'employee_code' => 'ITM1',
            'department_id' => $dept->id,
            'status' => 'active',
        ]);
        $hrEmp = Employee::create([
            'name' => 'HR Manager Emp',
            'employee_code' => 'HRM1',
            'department_id' => $dept->id,
            'status' => 'active',
        ]);

        $managerUser = User::factory()->create([
            'role' => User::ROLE_MANAGER,
            'employee_id' => $managerEmp->id,
        ]);
        $hrUser = User::factory()->create([
            'role' => User::ROLE_HR,
            'employee_id' => $hrEmp->id,
        ]);

        $type = LeaveType::create(['name' => 'Annual', 'annual_quota' => 14, 'is_paid' => true]);

        $futureDay = now()->next('Monday');

        $managerLeave = LeaveRequest::create([
            'employee_id' => $managerEmp->id,
            'leave_type_id' => $type->id,
            'start_date' => $futureDay->toDateString(),
            'end_date' => $futureDay->toDateString(),
            'days' => 1,
            'status' => LeaveRequest::STATUS_PENDING,
        ]);

        // Manager tries to approve own leave request -> BLOCKED
        $this->actingAs($managerUser)
            ->post(route('leave.approve', $managerLeave))
            ->assertForbidden();
        $this->assertSame(LeaveRequest::STATUS_PENDING, $managerLeave->fresh()->status);

        // HR Manager approves Manager's leave request -> ALLOWED
        $this->actingAs($hrUser)
            ->post(route('leave.approve', $managerLeave))
            ->assertSessionHas('success');
        $this->assertSame(LeaveRequest::STATUS_APPROVED, $managerLeave->fresh()->status);

        // HR Manager tries to approve OWN leave request -> BLOCKED
        $hrLeave = LeaveRequest::create([
            'employee_id' => $hrEmp->id,
            'leave_type_id' => $type->id,
            'start_date' => $futureDay->toDateString(),
            'end_date' => $futureDay->toDateString(),
            'days' => 1,
            'status' => LeaveRequest::STATUS_PENDING,
        ]);

        $this->actingAs($hrUser)
            ->post(route('leave.approve', $hrLeave))
            ->assertForbidden();
        $this->assertSame(LeaveRequest::STATUS_PENDING, $hrLeave->fresh()->status);

        // Admin approves HR Manager's leave request -> ALLOWED
        $adminEmp = Employee::create([
            'name' => 'Admin Emp',
            'employee_code' => 'ADM1',
            'status' => 'active',
        ]);
        $adminUser = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'employee_id' => $adminEmp->id,
        ]);

        $this->actingAs($adminUser)
            ->post(route('leave.approve', $hrLeave))
            ->assertSessionHas('success');
        $this->assertSame(LeaveRequest::STATUS_APPROVED, $hrLeave->fresh()->status);

        // Admin tries to approve OWN leave request -> BLOCKED
        $adminLeave = LeaveRequest::create([
            'employee_id' => $adminEmp->id,
            'leave_type_id' => $type->id,
            'start_date' => $futureDay->toDateString(),
            'end_date' => $futureDay->toDateString(),
            'days' => 1,
            'status' => LeaveRequest::STATUS_PENDING,
        ]);

        $this->actingAs($adminUser)
            ->post(route('leave.approve', $adminLeave))
            ->assertForbidden();
        $this->assertSame(LeaveRequest::STATUS_PENDING, $adminLeave->fresh()->status);

        // Super Admin approves Admin's leave request & everyone's request -> ALLOWED
        $superAdminUser = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
        ]);

        $this->actingAs($superAdminUser)
            ->post(route('leave.approve', $adminLeave))
            ->assertSessionHas('success');
        $this->assertSame(LeaveRequest::STATUS_APPROVED, $adminLeave->fresh()->status);
    }

    public function test_leave_request_delete_hierarchy_rules(): void
    {
        $dept = Department::create(['name' => 'Ops Dept']);

        $empWorker = Employee::create(['name' => 'Worker 1', 'employee_code' => 'W1', 'department_id' => $dept->id, 'status' => 'active']);
        $empManager = Employee::create(['name' => 'Ops Manager', 'employee_code' => 'M1', 'department_id' => $dept->id, 'status' => 'active']);
        $empHr = Employee::create(['name' => 'HR User', 'employee_code' => 'H1', 'status' => 'active']);
        $empAdmin = Employee::create(['name' => 'Admin User', 'employee_code' => 'AD1', 'status' => 'active']);

        $userWorker = User::factory()->create(['role' => User::ROLE_USER, 'employee_id' => $empWorker->id]);
        $userManager = User::factory()->create(['role' => User::ROLE_MANAGER, 'employee_id' => $empManager->id]);
        $userHr = User::factory()->create(['role' => User::ROLE_HR, 'employee_id' => $empHr->id]);
        $userAdmin = User::factory()->create(['role' => User::ROLE_ADMIN, 'employee_id' => $empAdmin->id]);
        $userSuperAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $type = LeaveType::create(['name' => 'Casual', 'annual_quota' => 10, 'is_paid' => true]);
        $futureDay = now()->next('Monday');

        $leaveWorker = LeaveRequest::create(['employee_id' => $empWorker->id, 'leave_type_id' => $type->id, 'start_date' => $futureDay->toDateString(), 'end_date' => $futureDay->toDateString(), 'days' => 1, 'status' => LeaveRequest::STATUS_PENDING]);
        $leaveManager = LeaveRequest::create(['employee_id' => $empManager->id, 'leave_type_id' => $type->id, 'start_date' => $futureDay->toDateString(), 'end_date' => $futureDay->toDateString(), 'days' => 1, 'status' => LeaveRequest::STATUS_PENDING]);
        $leaveHr = LeaveRequest::create(['employee_id' => $empHr->id, 'leave_type_id' => $type->id, 'start_date' => $futureDay->toDateString(), 'end_date' => $futureDay->toDateString(), 'days' => 1, 'status' => LeaveRequest::STATUS_PENDING]);
        $leaveAdmin = LeaveRequest::create(['employee_id' => $empAdmin->id, 'leave_type_id' => $type->id, 'start_date' => $futureDay->toDateString(), 'end_date' => $futureDay->toDateString(), 'days' => 1, 'status' => LeaveRequest::STATUS_PENDING]);

        // 1. Manager tries to delete OWN leave -> BLOCKED
        $this->actingAs($userManager)->delete(route('leave.destroy', $leaveManager))->assertForbidden();

        // 2. Manager tries to delete HR leave -> BLOCKED
        $this->actingAs($userManager)->delete(route('leave.destroy', $leaveHr))->assertForbidden();

        // 3. Manager deletes Employee leave -> ALLOWED
        $this->actingAs($userManager)->delete(route('leave.destroy', $leaveWorker))->assertRedirect();
        $this->assertDatabaseMissing('leave_requests', ['id' => $leaveWorker->id]);

        // 4. HR Manager tries to delete OWN leave -> BLOCKED
        $this->actingAs($userHr)->delete(route('leave.destroy', $leaveHr))->assertForbidden();

        // 5. HR Manager tries to delete Admin leave -> BLOCKED
        $this->actingAs($userHr)->delete(route('leave.destroy', $leaveAdmin))->assertForbidden();

        // 6. HR Manager deletes Manager leave -> ALLOWED
        $this->actingAs($userHr)->delete(route('leave.destroy', $leaveManager))->assertRedirect();
        $this->assertDatabaseMissing('leave_requests', ['id' => $leaveManager->id]);

        // 7. Admin tries to delete OWN leave -> BLOCKED
        $this->actingAs($userAdmin)->delete(route('leave.destroy', $leaveAdmin))->assertForbidden();

        // 8. Admin deletes HR Manager leave -> ALLOWED
        $this->actingAs($userAdmin)->delete(route('leave.destroy', $leaveHr))->assertRedirect();
        $this->assertDatabaseMissing('leave_requests', ['id' => $leaveHr->id]);

        // 9. Super Admin deletes Admin leave -> ALLOWED
        $this->actingAs($userSuperAdmin)->delete(route('leave.destroy', $leaveAdmin))->assertRedirect();
        $this->assertDatabaseMissing('leave_requests', ['id' => $leaveAdmin->id]);
    }
}
