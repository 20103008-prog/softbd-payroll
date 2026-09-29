<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeShiftAssignment;
use App\Models\Shift;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RosterController extends Controller
{
    /**
     * Display roster management view with shifts and employee shift assignments.
     */
    public function index(): View
    {
        $shifts = Shift::query()
            ->withCount(['assignments' => function ($q) {
                $q->where(function ($q) {
                    $q->whereNull('effective_to')
                        ->orWhere('effective_to', '>=', now()->toDateString());
                });
            }])
            ->orderBy('start_time')
            ->get();

        $assignments = EmployeeShiftAssignment::query()
            ->with(['employee.user', 'shift'])
            ->orderByDesc('effective_from')
            ->paginate(25);

        return view('roster.index', [
            'shifts' => $shifts,
            'assignments' => $assignments,
            'employees' => Employee::with('user')->orderBy('name')->get(),
        ]);
    }

    /**
     * Store a newly created work shift schedule in storage.
     */
    public function storeShift(Request $request): RedirectResponse
    {
        $validated = $this->validateShift($request);

        Shift::create($validated);

        return redirect()->route('roster.index')->with('success', 'Shift created.');
    }

    public function updateShift(Request $request, Shift $shift): RedirectResponse
    {
        $validated = $this->validateShift($request, $shift);

        $shift->update($validated);

        return redirect()->route('roster.index')->with('success', 'Shift updated.');
    }

    public function destroyShift(Shift $shift): RedirectResponse
    {
        if ($shift->assignments()->exists()) {
            return back()->with('error', 'Cannot delete a shift that still has assignments. Remove assignments first.');
        }

        $shift->delete();

        return redirect()->route('roster.index')->with('success', 'Shift deleted.');
    }

    public function storeAssignment(Request $request): RedirectResponse
    {
        /* ========================================================================= */
        /* CUSTOM EDIT: RESTRICT PAST DATES USING after_or_equal:today RULE */
        /* ========================================================================= */
        $validated = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'shift_id' => ['required', 'exists:shifts,id'],
            'effective_from' => ['required', 'date', 'after_or_equal:today'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ], [
            'effective_from.after_or_equal' => 'The Start Date (From) cannot be a past date.',
            'effective_to.after_or_equal' => 'The End Date (To) must be after or equal to the Start Date.',
        ]);

        EmployeeShiftAssignment::create([
            'employee_id' => $validated['employee_id'],
            'shift_id' => $validated['shift_id'],
            'effective_from' => $validated['effective_from'],
            'effective_to' => $validated['effective_to'] ?? null,
        ]);

        return redirect()->route('roster.index')->with('success', 'Shift assigned to employee.');
    }

    public function destroyAssignment(EmployeeShiftAssignment $assignment): RedirectResponse
    {
        $assignment->delete();

        return redirect()->route('roster.index')->with('success', 'Assignment removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateShift(Request $request, ?Shift $shift = null): array
    {
        $nameRule = Rule::unique('shifts', 'name');
        if ($shift) {
            $nameRule = $nameRule->ignore($shift->id);
        }

        // Browsers may send H:i or H:i:s from <input type="time">.
        $request->merge([
            'start_time' => substr((string) $request->input('start_time'), 0, 5),
            'end_time' => substr((string) $request->input('end_time'), 0, 5),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', $nameRule],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'break_minutes' => ['nullable', 'integer', 'min:0', 'max:480'],
            'grace_minutes' => ['nullable', 'integer', 'min:0', 'max:600'],
            'overtime_starts_after_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'is_overnight' => ['nullable', 'boolean'],
        ]);

        $start = $validated['start_time'];
        $end = $validated['end_time'];
        $overnight = $request->boolean('is_overnight') || $end <= $start;

        return [
            'name' => $validated['name'],
            'start_time' => $start.':00',
            'end_time' => $end.':00',
            'break_minutes' => (int) ($validated['break_minutes'] ?? 0),
            'grace_minutes' => (int) ($validated['grace_minutes'] ?? 0),
            'overtime_starts_after_minutes' => (int) ($validated['overtime_starts_after_minutes'] ?? 0),
            'timezone' => config('app.timezone', 'UTC'),
            'is_overnight' => $overnight,
        ];
    }
}
