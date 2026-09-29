<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\SalaryStructure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PayrollLockTest extends TestCase
{
    use RefreshDatabase;

    public function test_locked_payroll_cannot_be_deleted(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $run = PayrollRun::create([
            'year' => 2026,
            'month' => 7,
            'working_days' => 22,
            'standard_daily_hours' => 8,
            'status' => PayrollRun::STATUS_LOCKED,
            'generated_at' => now(),
            'locked_at' => now(),
        ]);

        Payslip::create([
            'payroll_run_id' => $run->id,
            'employee_code' => '1001',
            'employee_name' => 'Test',
            'monthly_salary' => 10000,
            'working_days' => 22,
            'present_days' => 20,
            'absent_days' => 2,
            'paid_leave_days' => 0,
            'late_days' => 0,
            'half_days' => 0,
            'overtime_hours' => 0,
            'overtime_amount' => 0,
            'allowances_total' => 0,
            'absent_deduction' => 0,
            'late_deduction' => 0,
            'component_deductions' => 0,
            'gross_salary' => 10000,
            'total_deductions' => 0,
            'net_salary' => 10000,
        ]);

        $this->actingAs($admin)
            ->delete(route('payroll.destroy', $run))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('payroll_runs', ['id' => $run->id, 'status' => PayrollRun::STATUS_LOCKED]);
    }

    public function test_locked_payroll_cannot_be_regenerated(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $employee = \App\Models\Employee::create([
            'name' => 'Paid Emp',
            'employee_code' => 'P1',
            'status' => 'active',
        ]);
        \App\Models\SalaryStructure::create([
            'employee_id' => $employee->id,
            'monthly_salary' => 20000,
            'overtime_rate' => 0,
        ]);

        PayrollRun::create([
            'year' => (int) now()->year,
            'month' => (int) now()->month,
            'working_days' => 22,
            'standard_daily_hours' => 8,
            'status' => PayrollRun::STATUS_LOCKED,
            'generated_at' => now(),
            'locked_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('payroll.store'), [
                'period' => now()->format('Y-m'),
                'working_days' => 22,
                'standard_daily_hours' => 8,
            ])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_payroll_defaults_working_days_to_friday_saturday_weekend(): void
    {
        $this->travelTo(Carbon::create(2026, 8, 15, 12, 0, 0));

        $start = Carbon::create(2026, 8, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $friSatWorking = 0;
        $satSunWorking = 0;
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            if (! in_array((int) $d->dayOfWeek, Employee::DEFAULT_WEEKEND_DAYS, true)) {
                $friSatWorking++;
            }
            if (! $d->isWeekend()) {
                $satSunWorking++;
            }
        }
        $this->assertNotSame($friSatWorking, $satSunWorking);

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $employee = Employee::create([
            'name' => 'Paid Emp',
            'employee_code' => 'P2',
            'status' => 'active',
        ]);
        SalaryStructure::create([
            'employee_id' => $employee->id,
            'monthly_salary' => 20000,
            'overtime_rate' => 0,
        ]);

        $this->actingAs($admin)
            ->post(route('payroll.store'), [
                'period' => '2026-08',
                'standard_daily_hours' => 8,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('payroll_runs', [
            'year' => 2026,
            'month' => 8,
            'working_days' => $friSatWorking,
        ]);
    }
}
