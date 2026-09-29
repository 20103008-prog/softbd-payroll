<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\SalaryComponent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalaryComponentController extends Controller
{
    /**
     * Display a listing of custom salary earnings and deductions for an employee.
     */
    public function index(Employee $employee): View
    {
        $employee->load(['salaryStructure', 'salaryComponents' => fn ($q) => $q->orderBy('type')->orderBy('name')]);

        return view('payroll.components', [
            'employee' => $employee,
        ]);
    }

    /**
     * Store a newly created salary earning or deduction component for an employee.
     */
    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $validated = $this->validateComponent($request);

        $employee->salaryComponents()->create($validated);

        return redirect()
            ->route('payroll.components', $employee)
            ->with('success', 'Component added.');
    }

    /**
     * Update the specified salary component in storage.
     */
    public function update(Request $request, SalaryComponent $component): RedirectResponse
    {
        $validated = $this->validateComponent($request);

        $component->update($validated);

        return redirect()
            ->route('payroll.components', $component->employee_id)
            ->with('success', 'Component updated.');
    }

    /**
     * Remove the specified salary component from storage.
     */
    public function destroy(SalaryComponent $component): RedirectResponse
    {
        $employeeId = $component->employee_id;
        $component->delete();

        return redirect()
            ->route('payroll.components', $employeeId)
            ->with('success', 'Component removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateComponent(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:earning,deduction'],
            'calc_type' => ['required', 'in:fixed,percent'],
            'amount' => ['required', 'numeric', 'min:0', 'max:9999999'],
        ]);
    }
}
