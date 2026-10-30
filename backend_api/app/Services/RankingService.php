<?php

namespace App\Services;

use App\Models\Manhunt;
use App\Models\Subject;
use Illuminate\Support\Facades\DB;

class RankingService
{
    /**
     * Column names here are the only ones that ever reach the SQL, so
     * category input is safe once it has been checked against these keys.
     */
    public const CATEGORIES = [
        'length' => ['label' => 'Manhunt Length', 'column' => 'duration_seconds'],
        'officers_involved' => ['label' => 'Officers Involved', 'column' => 'officers_involved'],
        'officers_injured' => ['label' => 'Officers Injured', 'column' => 'officers_injured'],
        'units_stood_down' => ['label' => 'Units Stood Down', 'column' => 'units_stood_down'],
        'checkpoints' => ['label' => 'Checkpoints Evaded', 'column' => 'checkpoints'],
        'k9_units' => ['label' => 'K-9 Units Deployed', 'column' => 'k9_units'],
        'helicopters' => ['label' => 'Helicopters Deployed', 'column' => 'helicopters'],
        'cost' => ['label' => 'Cost to State', 'column' => 'cost_to_state'],
        'bounty' => ['label' => 'Bounty', 'column' => 'bounty_value'],
        'infractions' => ['label' => 'Infractions Recorded', 'column' => null],
    ];

    /**
     * @param  'single'|'lifetime'  $scope  single = best single manhunt, lifetime = totals per subject
     * @return array<int,array<string,mixed>>
     */
    public function top(string $category, string $scope, int $limit = 10): array
    {
        $rows = $scope === 'single'
            ? $this->single($category, $limit)
            : $this->lifetime($category, $limit);

        return collect($rows)->values()->map(fn (array $row, int $index) => ['rank' => $index + 1] + $row)->all();
    }

    private function single(string $category, int $limit): array
    {
        $query = Manhunt::query()->with('subject:id,case_id,name,alias');

        if ($category === 'infractions') {
            $query->withCount('infractions')->orderByDesc('infractions_count');
        } else {
            $query->orderByDesc(self::CATEGORIES[$category]['column']);
        }

        return $query->limit($limit)->get()
            ->filter(fn (Manhunt $manhunt) => $manhunt->subject !== null)
            ->map(fn (Manhunt $manhunt) => [
                'subject' => $this->subjectRow($manhunt->subject),
                'manhunt_code' => $manhunt->code,
                'value' => (int) ($category === 'infractions'
                    ? $manhunt->infractions_count
                    : $manhunt->{self::CATEGORIES[$category]['column']}),
            ])
            ->all();
    }

    private function lifetime(string $category, int $limit): array
    {
        // These two are already totals stored on the subject.
        $stored = ['bounty' => 'bounty', 'cost' => 'cost_to_state', 'infractions' => 'total_infractions'];

        if (isset($stored[$category])) {
            $column = $stored[$category];

            return Subject::query()
                ->where($column, '>', 0)
                ->orderByDesc($column)
                ->limit($limit)
                ->get()
                ->map(fn (Subject $subject) => [
                    'subject' => $this->subjectRow($subject),
                    'manhunt_code' => null,
                    'value' => (int) $subject->{$column},
                ])
                ->all();
        }

        $column = self::CATEGORIES[$category]['column'];

        return Manhunt::query()
            ->select('subject_id', DB::raw("SUM({$column}) AS total"))
            ->groupBy('subject_id')
            ->orderByDesc('total')
            ->limit($limit)
            ->with('subject:id,case_id,name,alias')
            ->get()
            ->filter(fn (Manhunt $row) => $row->subject !== null && (int) $row->total > 0)
            ->map(fn (Manhunt $row) => [
                'subject' => $this->subjectRow($row->subject),
                'manhunt_code' => null,
                'value' => (int) $row->total,
            ])
            ->all();
    }

    private function subjectRow(Subject $subject): array
    {
        return [
            'id' => $subject->id,
            'case_id' => $subject->case_id,
            'name' => $subject->name,
            'alias' => $subject->alias,
        ];
    }
}
