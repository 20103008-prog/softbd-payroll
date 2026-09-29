<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'logo_path',
    ];

    /**
     * The single, app-wide company profile (created with defaults if missing).
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'name' => config('app.name'),
        ]);
    }

    public function displayName(): string
    {
        return $this->name ?: (string) config('app.name', 'Company');
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? asset($this->logo_path) : null;
    }
}
