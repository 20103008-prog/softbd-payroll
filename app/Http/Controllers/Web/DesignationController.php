<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Designation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DesignationController extends Controller
{
    /**
     * Store a newly created designation in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:designations,name'],
        ]);

        Designation::create($validated);

        return back()->with('success', 'Designation added.');
    }

    /**
     * Update the specified designation in storage.
     */
    public function update(Request $request, Designation $designation): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('designations', 'name')->ignore($designation->id)],
        ]);

        $designation->update($validated);

        return back()->with('success', 'Designation updated.');
    }

    /**
     * Remove the specified designation from storage.
     */
    public function destroy(Designation $designation): RedirectResponse
    {
        $designation->delete();

        return back()->with('success', 'Designation deleted.');
    }
}
