<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Designation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    /**
     * Display a listing of departments and designations with employee counts.
     */
    public function index(): View
    {
        return view('organization.index', [
            'departments' => Department::withCount('employees')->orderBy('name')->get(),
            'designations' => Designation::withCount('employees')->orderBy('name')->get(),
        ]);
    }

    /**
     * Store a newly created department in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:departments,name'],
        ]);

        Department::create($validated);

        return back()->with('success', 'Department added.');
    }

    /**
     * Update the specified department in storage.
     */
    public function update(Request $request, Department $department): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('departments', 'name')->ignore($department->id)],
        ]);

        $department->update($validated);

        return back()->with('success', 'Department updated.');
    }

    /**
     * Remove the specified department from storage.
     */
    public function destroy(Department $department): RedirectResponse
    {
        $department->delete();

        return back()->with('success', 'Department deleted.');
    }
}
