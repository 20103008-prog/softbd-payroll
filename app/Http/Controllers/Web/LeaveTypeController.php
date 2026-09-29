<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\LeaveType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveTypeController extends Controller
{
    /**
     * Display a listing of leave types and annual quotas.
     */
    public function index(): View
    {
        return view('leave.types', [
            'leaveTypes' => LeaveType::withCount('leaveRequests')->orderBy('name')->get(),
        ]);
    }

    /**
     * Store a newly created leave type in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'annual_quota' => ['required', 'integer', 'min:0', 'max:365'],
            'is_paid' => ['nullable', 'boolean'],
        ]);

        LeaveType::create([
            'name' => $validated['name'],
            'annual_quota' => $validated['annual_quota'],
            'is_paid' => $request->boolean('is_paid'),
        ]);

        return back()->with('success', 'Leave type added.');
    }

    /**
     * Update the specified leave type in storage.
     */
    public function update(Request $request, LeaveType $leaveType): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'annual_quota' => ['required', 'integer', 'min:0', 'max:365'],
            'is_paid' => ['nullable', 'boolean'],
        ]);

        $leaveType->update([
            'name' => $validated['name'],
            'annual_quota' => $validated['annual_quota'],
            'is_paid' => $request->boolean('is_paid'),
        ]);

        return back()->with('success', 'Leave type updated.');
    }

    /**
     * Remove the specified leave type from storage.
     */
    public function destroy(LeaveType $leaveType): RedirectResponse
    {
        $leaveType->delete();

        return back()->with('success', 'Leave type deleted.');
    }
}
