<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Device extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'serial_number',
        'ip_address',
        'port',
        'is_active',
        'last_seen_at',
    ];

    protected $casts = [
        'port' => 'integer',
        'is_active' => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    public function attendanceLogs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class);
    }
}
