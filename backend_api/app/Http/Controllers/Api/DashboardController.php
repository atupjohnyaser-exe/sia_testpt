<?php

namespace App\Http\Controllers\Api;

use App\Enums\SubjectStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\SubjectResource;
use App\Models\Bulletin;
use App\Models\Manhunt;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        return response()->json([
            'data' => [
                'active_records' => Subject::active()->count(),
                'outstanding_warrants' => Subject::where('status', SubjectStatus::Wanted->value)->count(),
                'in_custody' => Subject::where('status', SubjectStatus::InCustody->value)->count(),
                'critical_threat' => Subject::active()->where('threat_level', '>=', 5)->count(),
                'total_bounty' => (int) Subject::active()->sum('bounty'),
                'total_cost_to_state' => (int) Subject::sum('cost_to_state'),
                'manhunts_logged' => Manhunt::count(),
                'new_this_week' => Subject::where('created_at', '>=', now()->startOfWeek())->count(),
            ],
        ]);
    }

    /** The five highest bounties among people who are not cleared. */
    public function priority()
    {
        return SubjectResource::collection(
            Subject::active()->orderByDesc('bounty')->orderBy('id')->limit(5)->get()
        );
    }

    /** Rotates through active bulletins every 30 seconds. */
    public function bulletin(): JsonResponse
    {
        $bulletins = Bulletin::where('is_active', true)->orderBy('published_at')->orderBy('id')->get();

        if ($bulletins->isEmpty()) {
            return response()->json(['data' => null]);
        }

        $current = $bulletins[intdiv(time(), 30) % $bulletins->count()];

        return response()->json([
            'data' => [
                'id' => $current->id,
                'ref' => $current->ref,
                'body' => $current->body,
                'published_at' => $current->published_at,
            ],
        ]);
    }
}
