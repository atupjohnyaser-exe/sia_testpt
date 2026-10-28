<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubjectResource;
use App\Models\Bulletin;
use App\Models\Offense;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function stats(Request $request)
    {
        abort_unless((int) $request->user()->clearance_level >= 3, 403);
        return response()->json(Cache::remember('rpd.dashboard.stats', now()->addSeconds(30), fn () => [
            'active_records' => Subject::where('status', '!=', 'CLEARED')->count(),
            'outstanding_warrants' => Subject::where('status', 'WANTED')->count(),
            'recorded_offenses' => Offense::count(),
            'critical_risk' => Subject::where('risk_score', '>=', 80)->count(),
            'new_this_week' => Subject::where('created_at', '>=', now()->startOfWeek())->count(),
        ]));
    }

    public function prioritySubjects(Request $request)
    {
        abort_unless((int) $request->user()->clearance_level >= 3, 403);
        return SubjectResource::collection(Subject::withCount('offenses')->orderByDesc('risk_score')->limit(3)->get());
    }

    public function bulletin()
    {
        $items = Bulletin::where('is_active', true)->orderBy('published_at')->get();
        if ($items->isEmpty()) return response()->json(['bulletin' => null]);
        $index = intdiv(now()->timestamp, 30) % $items->count();
        return response()->json(['bulletin' => $items->values()->get($index)]);
    }
}
