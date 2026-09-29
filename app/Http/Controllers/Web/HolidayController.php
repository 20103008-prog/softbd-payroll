<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HolidayController extends Controller
{
    /**
     * Display a listing of public holidays filtered by year.
     */
    public function index(Request $request): View
    {
        $year = (int) $request->query('year', now()->year);

        $holidays = Holiday::query()
            ->whereYear('date', $year)
            ->orderBy('date')
            ->get();

        $years = range(now()->year - 2, now()->year + 2);

        return view('holidays.index', [
            'holidays' => $holidays,
            'year' => $year,
            'years' => $years,
        ]);
    }

    /**
     * Store a newly created public holiday in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date', 'unique:holidays,date'],
            'name' => ['required', 'string', 'max:100'],
        ]);

        Holiday::create($validated);

        return back()->with('success', 'Holiday added.');
    }

    /**
     * Remove the specified public holiday from storage.
     */
    public function destroy(Holiday $holiday): RedirectResponse
    {
        $holiday->delete();

        return back()->with('success', 'Holiday removed.');
    }
}
