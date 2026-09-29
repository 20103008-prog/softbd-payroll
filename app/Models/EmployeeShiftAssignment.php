<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class EmployeeShiftAssignment extends Model
{
    protected $fillable = [
        'employee_id',
        'shift_id',
        'effective_from',
        'effective_to',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function covers(Carbon|string $date): bool
    {
        $d = $date instanceof Carbon ? $date->copy()->startOfDay() : Carbon::parse($date)->startOfDay();

        if ($d->lt($this->effective_from->copy()->startOfDay())) {
            return false;
        }

        if ($this->effective_to === null) {
            return true;
        }

        return $d->lte($this->effective_to->copy()->startOfDay());
    }

    public function periodLabel(): string
    {
        $from = $this->effective_from->format('M j, Y');
        $to = $this->effective_to ? $this->effective_to->format('M j, Y') : 'ongoing';

        return "{$from} → {$to}";
    }
}
