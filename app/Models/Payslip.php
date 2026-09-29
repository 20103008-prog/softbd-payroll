<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payslip extends Model
{
    protected $fillable = [
        'payroll_run_id',
        'employee_id',
        'employee_code',
        'employee_name',
        'monthly_salary',
        'working_days',
        'present_days',
        'absent_days',
        'paid_leave_days',
        'late_days',
        'half_days',
        'overtime_hours',
        'overtime_amount',
        'allowances_total',
        'absent_deduction',
        'late_deduction',
        'component_deductions',
        'gross_salary',
        'total_deductions',
        'net_salary',
    ];

    protected $casts = [
        'monthly_salary' => 'decimal:2',
        'working_days' => 'integer',
        'present_days' => 'integer',
        'absent_days' => 'integer',
        'paid_leave_days' => 'integer',
        'late_days' => 'integer',
        'half_days' => 'integer',
        'overtime_hours' => 'decimal:2',
        'overtime_amount' => 'decimal:2',
        'allowances_total' => 'decimal:2',
        'absent_deduction' => 'decimal:2',
        'late_deduction' => 'decimal:2',
        'component_deductions' => 'decimal:2',
        'gross_salary' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_salary' => 'decimal:2',
    ];

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayslipItem::class);
    }
}
