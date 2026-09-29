<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * AuditLogger Service
 *
 * Records user actions, IP addresses, and audited subject details into the database.
 */
class AuditLogger
{
    public function log(string $action, string $summary, ?Model $subject = null, array $properties = []): void
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'auditable_type' => $subject ? $subject->getMorphClass() : null,
            'auditable_id' => $subject?->getKey(),
            'summary' => mb_substr($summary, 0, 500),
            'properties' => $properties ?: null,
            'ip_address' => Request::ip(),
        ]);
    }
}
