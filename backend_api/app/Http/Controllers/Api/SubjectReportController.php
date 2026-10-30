<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Services\CostToStateCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * The read-only report screens of a record: summary, infractions by type,
 * cost to state, and known locations.
 */
class SubjectReportController extends Controller
{
    /** Header and summary numbers. */
    public function rapSheet(Subject $subject): JsonResponse
    {
        $police = DB::table('manhunts')
            ->where('subject_id', $subject->id)
            ->selectRaw('COUNT(*) AS manhunts, COALESCE(SUM(officers_injured), 0) AS injured, COALESCE(SUM(units_stood_down), 0) AS stood_down')
            ->first();

        return response()->json(['data' => [
            'subject' => [
                'id' => $subject->id,
                'case_id' => $subject->case_id,
                'name' => $subject->name,
                'alias' => $subject->alias,
                'status' => $subject->status->value,
            ],
            'bounty' => $subject->bounty,
            'cost_to_state' => $subject->cost_to_state,
            'threat' => [
                'level' => $subject->threat_level,
                'label' => $subject->threat()->label(),
                'response' => $subject->threat()->response(),
            ],
            'infractions' => [
                'unserved' => $subject->unserved_infractions,
                'served' => $subject->total_infractions - $subject->unserved_infractions,
            ],
            'fines' => ['due' => $subject->fines_due, 'paid' => $subject->fines_paid],
            'manhunts' => [
                'total' => (int) $police->manhunts,
                'times_evaded' => $subject->times_evaded,
                'times_apprehended' => $subject->times_apprehended,
            ],
            'police' => [
                'officers_injured' => (int) $police->injured,
                'units_stood_down' => (int) $police->stood_down,
            ],
        ]]);
    }

    /** Infractions grouped by type, unserved and total. */
    public function infractionSummary(Subject $subject): JsonResponse
    {
        $rows = DB::table('infractions')
            ->join('infraction_types AS t', 't.id', '=', 'infractions.infraction_type_id')
            ->where('infractions.subject_id', $subject->id)
            ->groupBy('t.id', 't.code', 't.name', 't.severity')
            ->selectRaw('t.code, t.name, t.severity, COUNT(*) AS total, SUM(CASE WHEN infractions.served_at IS NULL THEN 1 ELSE 0 END) AS unserved')
            ->orderByDesc('unserved')
            ->orderBy('t.name')
            ->get()
            ->map(fn ($row) => [
                'code' => $row->code,
                'name' => $row->name,
                'severity' => $row->severity,
                'unserved' => (int) $row->unserved,
                'total' => (int) $row->total,
            ]);

        return response()->json(['data' => [
            'unserved' => $subject->unserved_infractions,
            'fines_due' => $subject->fines_due,
            'types' => $rows,
        ]]);
    }

    /** Quantity x unit cost per category, added up over all manhunts. */
    public function costToState(Subject $subject, CostToStateCalculator $costs): JsonResponse
    {
        $table = $costs->aggregate($subject->manhunts()->get(['id', 'cost_breakdown']));

        return response()->json(['data' => [
            'bounty' => $subject->bounty,
            'lines' => $table['lines'],
            'total' => $table['total'],
        ]]);
    }

    /** Sightings grouped by district, plus the latest ones. */
    public function locations(Subject $subject): JsonResponse
    {
        $districts = DB::table('sightings')
            ->where('subject_id', $subject->id)
            ->groupBy('district')
            ->selectRaw('district, COUNT(*) AS sightings, MAX(seen_at) AS last_seen')
            ->orderByDesc('last_seen')
            ->get()
            ->map(fn ($row) => [
                'district' => $row->district,
                'sightings' => (int) $row->sightings,
                'last_seen' => $row->last_seen,
            ]);

        $recent = $subject->sightings()->latest('seen_at')->limit(10)->get()->map(fn ($s) => [
            'id' => $s->id,
            'district' => $s->district,
            'address' => $s->address,
            'seen_at' => $s->seen_at,
            'source' => $s->source,
        ]);

        return response()->json(['data' => [
            'last_known_location' => $subject->last_known_location,
            'districts' => $districts,
            'recent' => $recent,
        ]]);
    }
}
