<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryComponent extends Model
{
    public const TYPE_EARNING = 'earning';
    public const TYPE_DEDUCTION = 'deduction';

    public const CALC_FIXED = 'fixed';
    public const CALC_PERCENT = 'percent';

    protected $fillable = [
        'employee_id',
        'name',
        'type',
        'calc_type',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Resolve this component to a money amount given a basic salary.
     */
    public function resolve(float $basic): float
    {
        if ($this->calc_type === self::CALC_PERCENT) {
            return round($basic * ((float) $this->amount) / 100, 2);
        }

        return round((float) $this->amount, 2);
    }

    public function isEarning(): bool
    {
        return $this->type === self::TYPE_EARNING;
    }
}
