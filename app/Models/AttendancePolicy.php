<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class AttendancePolicy extends Model
{
    protected $fillable = [
        'work_start_time',
        'grace_minutes',
        'half_day_after_minutes',
    ];

    protected $casts = [
        'grace_minutes' => 'integer',
        'half_day_after_minutes' => 'integer',
    ];

    /**
     * The single, app-wide policy row (created with defaults if missing).
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'work_start_time' => '09:00:00',
            'grace_minutes' => 0,
        ]);
    }

    /**
     * "HH:MM" form for a time input.
     */
    public function startTimeForInput(): string
    {
        return substr((string) $this->work_start_time, 0, 5);
    }

    /**
     * Format work start time in 12-hour AM/PM format (e.g. 9:00 AM).
     */
   //public function formattedStartTime(): string
    //{
        //return Carbon::parse($this->work_start_time)->format('g:i A');
   // }
}
