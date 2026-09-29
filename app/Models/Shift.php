<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Shift extends Model
{
    protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'break_minutes',
        'grace_minutes',
        'overtime_starts_after_minutes',
        'timezone',
        'is_overnight',
    ];

    protected $casts = [
        'break_minutes' => 'integer',
        'grace_minutes' => 'integer',
        'overtime_starts_after_minutes' => 'integer',
        'is_overnight' => 'boolean',
    ];

    public function assignments(): HasMany
    {
        return $this->hasMany(EmployeeShiftAssignment::class);
    }

    public function startTimeForInput(): string
    {
        return substr((string) $this->start_time, 0, 5);
    }

    public function endTimeForInput(): string
    {
        return substr((string) $this->end_time, 0, 5);
    }

    /**
     * Format shift start time in 12-hour AM/PM format (e.g. 9:00 AM).
     */
    public function formattedStartTime(): string
    {
        return Carbon::parse($this->start_time)->format('g:i A');
    }

    /**
     * Format shift end time in 12-hour AM/PM format (e.g. 5:00 PM).
     */
    public function formattedEndTime(): string
    {
        return Carbon::parse($this->end_time)->format('g:i A');
    }

    /**
     * Human-readable shift label with 12-hour AM/PM time range for dropdown selects.
     */
    public function label(): string
    {
        return sprintf('%s (%s – %s)', $this->name, $this->formattedStartTime(), $this->formattedEndTime());
    }

    /**
     * Scheduled start datetime for a given work date.
     */
    public function startAt(Carbon|string $workDate): Carbon
    {
        $date = $workDate instanceof Carbon ? $workDate->toDateString() : $workDate;

        return Carbon::parse($date.' '.$this->start_time);
    }

    /**
     * Scheduled end datetime (rolls to next day when overnight).
     */
    public function endAt(Carbon|string $workDate): Carbon
    {
        $start = $this->startAt($workDate);
        $end = Carbon::parse($start->toDateString().' '.$this->end_time);

        if ($this->is_overnight || $end->lte($start)) {
            $end->addDay();
        }

        return $end;
    }

    /**
     * Expected working minutes excluding break.
     */
    public function expectedWorkedMinutes(): int
    {
        $start = $this->startAt(now()->toDateString());
        $end = $this->endAt(now()->toDateString());

        return max(0, $start->diffInMinutes($end) - (int) $this->break_minutes);
    }
}
