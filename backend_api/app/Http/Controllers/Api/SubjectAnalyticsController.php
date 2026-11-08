<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

/** Numbers for the charts on a record. Grouped here so the browser only has to draw them. */
class SubjectAnalyticsController extends Controller
{
    public function show(Subject $subject): JsonResponse
    {
        $infractions = $subject->infractions()->with('type')->get();
        $manhunts = $subject->manhunts()->orderBy('started_at')->orderBy('id')->get();
        $sightings = $subject->sightings()->get();

        $unserved = $infractions->whereNull('served_at')->count();

        return response()->json(['data' => [
            'infractions_by_type' => $infractions
                ->groupBy(fn ($infraction) => $infraction->type->code)
                ->map(fn (Collection $rows) => [
                    'code' => $rows->first()->type->code,
                    'name' => $rows->first()->type->name,
                    'total' => $rows->count(),
                    'unserved' => $rows->whereNull('served_at')->count(),
                ])
                ->sortByDesc('total')
                ->values(),

            // High first, because the pyramid is drawn from the top.
            'infractions_by_severity' => collect(['HIGH', 'MEDIUM', 'LOW'])->map(fn (string $severity) => [
                'severity' => $severity,
                'total' => $infractions->filter(fn ($infraction) => $infraction->type->severity->value === $severity)->count(),
            ])->values(),

            'served' => ['served' => $infractions->count() - $unserved, 'unserved' => $unserved],

            'bounty_timeline' => $this->bountyTimeline($infractions, $manhunts),

            'manhunt_costs' => $manhunts->map(fn ($manhunt) => [
                'code' => $manhunt->code,
                'date' => $manhunt->started_at->toDateString(),
                'total' => $manhunt->cost_to_state,
                'lines' => collect($manhunt->cost_breakdown ?? [])->map(fn (array $line) => [
                    'code' => $line['code'],
                    'label' => $line['label'],
                    'cost' => $line['cost'],
                ])->values(),
            ])->values(),

            'sightings_by_place' => $sightings->groupBy('district')
                ->map(fn (Collection $rows, string $place) => ['name' => $place, 'count' => $rows->count()])
                ->sortByDesc('count')->take(8)->values(),

            'sightings_by_month' => $sightings->groupBy(fn ($sighting) => $sighting->seen_at->format('Y-m'))
                ->map(fn (Collection $rows, string $month) => ['month' => $month, 'count' => $rows->count()])
                ->sortKeys()->values(),

            'progress' => [
                'infractions_served' => $this->percent($infractions->count() - $unserved, $infractions->count()),
                'fines_paid' => $this->percent($subject->fines_paid, $subject->fines_paid + $subject->fines_due),
                'manhunts_apprehended' => $this->percent($subject->times_apprehended, $manhunts->count()),
            ],
        ]]);
    }

    private function bountyTimeline(Collection $infractions, Collection $manhunts): Collection
    {
        $events = $infractions
            ->map(fn ($infraction) => ['month' => $infraction->occurred_on->format('Y-m'), 'amount' => $infraction->bounty_value])
            ->merge($manhunts->map(fn ($manhunt) => ['month' => $manhunt->started_at->format('Y-m'), 'amount' => $manhunt->bounty_value]));

        $running = 0;

        return $events->groupBy('month')
            ->map(fn (Collection $rows) => $rows->sum('amount'))
            ->sortKeys()
            ->map(function (int|float $added, string $month) use (&$running) {
                $running += $added;

                return ['month' => $month, 'added' => (int) $added, 'cumulative' => (int) $running];
            })
            ->values();
    }

    private function percent(int|float $part, int|float $whole): int
    {
        return $whole > 0 ? (int) round($part / $whole * 100) : 0;
    }
}
