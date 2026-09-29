<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    /**
     * Display a listing of system audit logs filtered by action type and search query.
     */
    public function index(Request $request): View
    {
        $action = $request->query('action');
        $search = trim((string) $request->query('q', ''));

        // Query audit logs with user relationships and filter criteria
        $logs = AuditLog::query()
            ->with('user:id,name,email')
            ->when($action, fn ($q) => $q->where('action', $action))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('summary', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('id')
            ->paginate(40)
            ->withQueryString();

        $actions = AuditLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        return view('audit.index', [
            'logs' => $logs,
            'actions' => $actions,
            'filters' => ['action' => $action, 'q' => $search],
        ]);
    }
}
