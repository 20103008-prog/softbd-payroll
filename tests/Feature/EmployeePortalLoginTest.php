<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeePortalLoginTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
        ]);
    }

    public function test_creating_employee_with_portal_login_creates_linked_user(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('employees.store'), [
                'name' => 'Portal Worker',
                'employee_code' => 'P100',
                'status' => 'active',
                'weekend_days' => [5, 6],
                'enable_portal_login' => '1',
                'portal_email' => 'portal.worker@example.com',
                'portal_password' => 'password',
                'portal_password_confirmation' => 'password',
            ])
            ->assertRedirect(route('employees.index'));

        $employee = Employee::query()->where('employee_code', 'P100')->first();
        $this->assertNotNull($employee);
        $this->assertSame('portal.worker@example.com', $employee->email);

        $user = User::query()->where('email', 'portal.worker@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame(User::ROLE_USER, $user->role);
        $this->assertSame($employee->id, $user->employee_id);
    }

    public function test_portal_user_login_redirects_to_my_attendance(): void
    {
        $employee = Employee::create([
            'name' => 'Portal Worker',
            'employee_code' => 'P101',
            'status' => 'active',
            'email' => 'portal.login@example.com',
        ]);

        User::factory()->create([
            'name' => $employee->name,
            'email' => 'portal.login@example.com',
            'password' => 'password',
            'role' => User::ROLE_USER,
            'employee_id' => $employee->id,
        ]);

        $this->post(route('login.attempt'), [
            'email' => 'portal.login@example.com',
            'password' => 'password',
        ])->assertRedirect(route('me.attendance'));

        $this->get('/')->assertRedirect(route('me.attendance'));
    }

    public function test_staff_admin_login_still_reaches_dashboard(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
            'role' => User::ROLE_SUPER_ADMIN,
        ]);

        $this->post(route('login.attempt'), [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])->assertRedirect(route('home'));

        $this->get('/')->assertOk();
    }

    public function test_cannot_wipe_staff_role_linked_user_via_employee_form(): void
    {
        $admin = $this->admin();

        $employee = Employee::create([
            'name' => 'Dept Manager',
            'employee_code' => 'M200',
            'status' => 'active',
            'email' => 'manager.link@example.com',
        ]);

        $manager = User::factory()->create([
            'name' => 'Dept Manager',
            'email' => 'manager.link@example.com',
            'role' => User::ROLE_MANAGER,
            'employee_id' => $employee->id,
        ]);

        $this->actingAs($admin)
            ->put(route('employees.update', $employee), [
                'name' => 'Dept Manager',
                'employee_code' => 'M200',
                'status' => 'active',
                'email' => 'manager.link@example.com',
                'weekend_days' => [5, 6],
                // Attempt to disable portal login — must not delete staff account.
            ])
            ->assertRedirect(route('employees.index'));

        $this->assertDatabaseHas('users', [
            'id' => $manager->id,
            'email' => 'manager.link@example.com',
            'role' => User::ROLE_MANAGER,
            'employee_id' => $employee->id,
        ]);
    }

    public function test_disabling_portal_login_deletes_portal_user(): void
    {
        $admin = $this->admin();

        $employee = Employee::create([
            'name' => 'Temp Portal',
            'employee_code' => 'P102',
            'status' => 'active',
            'email' => 'temp.portal@example.com',
        ]);

        $portal = User::factory()->create([
            'email' => 'temp.portal@example.com',
            'role' => User::ROLE_USER,
            'employee_id' => $employee->id,
        ]);

        $this->actingAs($admin)
            ->put(route('employees.update', $employee), [
                'name' => 'Temp Portal',
                'employee_code' => 'P102',
                'status' => 'active',
                'email' => 'temp.portal@example.com',
                'weekend_days' => [5, 6],
                // enable_portal_login omitted = disabled
            ])
            ->assertRedirect(route('employees.index'));

        $this->assertDatabaseMissing('users', ['id' => $portal->id]);
    }
}
