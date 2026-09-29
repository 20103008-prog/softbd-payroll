<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Reports\PeriodSummaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SummaryReportController extends Controller
{
    public function index(Request $request): View
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $from = $request->query('from', now()->startOfMonth()->toDateString());
        $to = $request->query('to', now()->toDateString());
        $tab = $request->query('tab', 'late');
        if (! in_array($tab, PeriodSummaryService::TABS, true)) {
            $tab = 'late';
        }

        return view('reports.summary', [
            'filters' => ['from' => $from, 'to' => $to, 'tab' => $tab],
            'teamScoped' => $user->isTeamScoped(),
            'dataUrl' => route('reports.summary.data'),
        ]);
    }

    public function data(Request $request, PeriodSummaryService $summaries): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $validated = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
            'tab' => ['required', Rule::in(PeriodSummaryService::TABS)],
        ]);

        $payload = $summaries->buildTab(
            $user,
            $validated['from'],
            $validated['to'],
            $validated['tab']
        );

        return response()->json($payload);
    }
}
