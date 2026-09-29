<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'days',
        'reason',
        'status',
        'reviewed_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'days' => 'integer',
        'reviewed_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function isOwnedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->employee_id && (int) $this->employee_id === (int) $user->employee_id) {
            return true;
        }

        $this->loadMissing('employee');
        $employee = $this->employee;

        if ($employee && ! empty($employee->email) && ! empty($user->email) && strtolower($user->email) === strtolower($employee->email)) {
            return true;
        }

        return false;
    }
}
