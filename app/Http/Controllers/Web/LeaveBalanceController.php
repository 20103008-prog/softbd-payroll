<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\LeaveType;
use App\Services\Leave\LeaveBalanceService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveBalanceController extends Controller
{
    /**
     * Display annual leave quotas, used days, and remaining balances for employees.
     */
    public function index(Request $request, LeaveBalanceService $service): View
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $year = (int) $request->query('year', now()->year);

        $types = LeaveType::orderBy('name')->get();

        $employeesQuery = Employee::query()->orderBy('name');
        $user->scopeManagedEmployees($employeesQuery);

        $employees = $employeesQuery
            ->paginate(25)
            ->withQueryString();

        $used = $service->usedMap($employees->getCollection()->pluck('id'), $year);

        return view('leave.balances', [
            'year' => $year,
            'years' => range(now()->year - 3, now()->year + 2),
            'types' => $types,
            'employees' => $employees,
            'used' => $used,
        ]);
    }
}
