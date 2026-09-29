<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class PayrollRun extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_GENERATED = 'generated';

    public const STATUS_LOCKED = 'locked';

    protected $fillable = [
        'year',
        'month',
        'working_days',
        'standard_daily_hours',
        'status',
        'notes',
        'generated_at',
        'locked_at',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'working_days' => 'integer',
        'standard_daily_hours' => 'decimal:2',
        'generated_at' => 'datetime',
        'locked_at' => 'datetime',
    ];

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    public function isLocked(): bool
    {
        return $this->status === self::STATUS_LOCKED;
    }

    public function canRegenerate(): bool
    {
        return ! $this->isLocked();
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_LOCKED => 'Locked',
            self::STATUS_GENERATED => 'Generated',
            default => 'Draft',
        };
    }

    public function periodStart(): Carbon
    {
        return Carbon::create($this->year, $this->month, 1)->startOfMonth();
    }

    public function periodEnd(): Carbon
    {
        return $this->periodStart()->copy()->endOfMonth();
    }

    public function periodLabel(): string
    {
        return $this->periodStart()->format('F Y');
    }
}
