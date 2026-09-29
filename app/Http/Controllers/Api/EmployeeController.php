<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreEmployeeRequest;
use App\Http\Requests\Api\UpdateEmployeeRequest;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * EmployeeController
 * 
 * Provides RESTful API endpoints for employee management (list, store, view, update, delete).
 */
class EmployeeController extends Controller
{
    /**
     * Display a paginated listing of employees with search and department filtering.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Query employees with relationships loaded
        $query = Employee::query()->with(['department', 'designation'])->orderBy('name');

        // Apply keyword search on employee_code, name, or department name
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('employee_code', 'like', '%'.$search.'%')
                    ->orWhere('name', 'like', '%'.$search.'%')
                    ->orWhereHas('department', fn ($d) => $d->where('name', 'like', '%'.$search.'%'))
                    ->orwhereHas('phone', fn ($d) => $d->where('string', 'like', '%'.$search.'%'));
            });
        }

        // Filter by specific department ID
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->query('department_id'));
        }

        $perPage = min((int) $request->query('per_page', 15), 100);

        return response()->json($query->paginate($perPage));
    }

    /**
     * Store a newly created employee record in the system.
     *
     * @param StoreEmployeeRequest $request
     * @return JsonResponse
     */
    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        // Save new employee data
        $employee = Employee::create($request->validated());

        return response()->json($employee, 201);
    }

    /**
     * Display the specified employee profile and statistics.
     *
     * @param Employee $employee
     * @return JsonResponse
     */
    public function show(Employee $employee): JsonResponse
    {
        // Return employee details with attendance logs count
        return response()->json($employee->loadCount('attendanceLogs'));
    }

    /**
     * Update the specified employee record.
     *
     * @param UpdateEmployeeRequest $request
     * @param Employee $employee
     * @return JsonResponse
     */
    public function update(UpdateEmployeeRequest $request, Employee $employee): JsonResponse
    {
        // Update employee details with validated inputs
        $employee->update($request->validated());

        return response()->json($employee->fresh());
    }

    /**
     * Remove the specified employee record.
     *
     * @param Employee $employee
     * @return JsonResponse
     */
    public function destroy(Employee $employee): JsonResponse
    {
        // Delete employee record
        $employee->delete();

        return response()->json(null, 204);
    }
}
