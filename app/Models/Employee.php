<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

class Employee extends Model
{
    use HasFactory;

    /** Weekly-off days used when an employee has none configured (0=Sun … 6=Sat). */
    public const DEFAULT_WEEKEND_DAYS = [5, 6]; // Friday, Saturday

    /** Short weekday labels keyed by Carbon dayOfWeek. */
    public const WEEKDAY_LABELS = [
        0 => 'Sun', 1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat',
    ];

    protected $fillable = [
        'name',
        'employee_code',
        'department_id',
        'designation_id',
        'email',
        'phone',
        'joining_date',
        'status',
        'address',
        'weekend_days',
    ];

    protected $casts = [
        'joining_date' => 'date',
        'weekend_days' => 'array',
    ];

    /**
     * The employee's weekly-off weekday numbers (0=Sun … 6=Sat).
     * NULL falls back to the app default; an empty array means "no weekend".
     *
     * @return list<int>
     */
    public function weekendDays(): array
    {
        if (is_array($this->weekend_days)) {
            return array_values(array_map('intval', $this->weekend_days));
        }

        return self::DEFAULT_WEEKEND_DAYS;
    }

    /**
     * Whether the given date is a weekly-off day for this employee.
     */
    public function isWeekend(Carbon $date): bool
    {
        return in_array((int) $date->dayOfWeek, $this->weekendDays(), true);
    }

    /**
     * Human-readable weekly-off summary, e.g. "Fri, Sat" or "None".
     */
    public function weekendLabel(): string
    {
        $days = $this->weekendDays();
        if ($days === []) {
            return 'None';
        }
        sort($days);

        return implode(', ', array_map(fn ($d) => self::WEEKDAY_LABELS[$d] ?? '?', $days));
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    public function attendanceLogs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class, 'employee_code', 'employee_code');
    }

    public function salaryStructure(): HasOne
    {
        return $this->hasOne(SalaryStructure::class);
    }

    public function salaryComponents(): HasMany
    {
        return $this->hasMany(SalaryComponent::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function shiftAssignments(): HasMany
    {
        return $this->hasMany(EmployeeShiftAssignment::class);
    }

    public function user(): HasOne
    {
        if (! \Illuminate\Support\Facades\Schema::hasColumn('users', 'employee_id')) {
            try {
                \Illuminate\Support\Facades\Schema::table('users', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->foreignId('employee_id')->nullable()->after('role')->constrained()->nullOnDelete();
                    $table->unique('employee_id');
                });
            } catch (\Throwable $e) {
                // Ignore if created concurrently
            }
        }

        return $this->hasOne(User::class);
    }
}
