<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\SalaryStructure;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalaryController extends Controller
{
    /**
     * Display a listing of employee salary structures with search filtering.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $employees = Employee::query()
            ->with(['salaryStructure', 'department', 'user'])
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('employee_code', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('payroll.salaries', [
            'employees' => $employees,
            'search' => $search,
        ]);
    }

    /**
     * Create or update basic monthly salary and overtime rate for an employee.
     */
    public function update(Request $request, Employee $employee, AuditLogger $audit): RedirectResponse
    {
        $validated = $request->validate([
            'monthly_salary' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'overtime_rate' => ['nullable', 'numeric', 'min:0', 'max:999999'],
        ]);

        $structure = SalaryStructure::updateOrCreate(
            ['employee_id' => $employee->id],
            [
                'monthly_salary' => $validated['monthly_salary'],
                'overtime_rate' => $validated['overtime_rate'] ?? 0,
            ]
        );

        $audit->log(
            'salary.updated',
            "Updated salary for {$employee->name} to {$validated['monthly_salary']}",
            $structure,
            $validated
        );

        return redirect()
            ->route('payroll.salaries', ['q' => $request->query('q')])
            ->with('success', "Salary saved for {$employee->name}.");
    }
}
