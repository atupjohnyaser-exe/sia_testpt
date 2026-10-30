<?php

namespace App\Services;

use App\Enums\ManhuntOutcome;
use App\Enums\SubjectStatus;
use App\Models\InfractionType;
use App\Models\Manhunt;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ManhuntService
{
    public function __construct(
        private CostToStateCalculator $costs,
        private InfractionService $infractions,
        private StatusService $status,
        private SubjectStatsService $stats,
    ) {
    }

    /**
     * Logs a manhunt and everything that follows from it, in one transaction:
     * cost and bounty, any infractions recorded during it, the status change
     * from the outcome, and the refreshed subject numbers.
     */
    public function log(Subject $subject, array $data, User $by): Manhunt
    {
        if ($subject->status === SubjectStatus::Cleared) {
            throw ValidationException::withMessages([
                'subject' => ['This record is cleared. Change its status before logging a manhunt.'],
            ]);
        }

        return DB::transaction(function () use ($subject, $data, $by) {
            $quantities = [];

            foreach (CostToStateCalculator::QUANTITY_FIELDS as $field) {
                $quantities[$field] = (int) ($data[$field] ?? 0);
            }

            $cost = $this->costs->breakdown($quantities);
            $bounty = (int) round($cost['total'] * (float) config('rockport.manhunt_bounty_multiplier'));
            $outcome = $data['outcome'] instanceof ManhuntOutcome ? $data['outcome'] : ManhuntOutcome::from($data['outcome']);

            $manhunt = Manhunt::create($quantities + [
                'code' => $this->newCode(),
                'subject_id' => $subject->id,
                'started_at' => Carbon::parse($data['started_at']),
                'duration_seconds' => (int) $data['duration_seconds'],
                'outcome' => $outcome,
                'district' => $data['district'],
                'cost_to_state' => $cost['total'],
                'bounty_value' => $bounty,
                'cost_breakdown' => $cost['lines'],
                'logged_by' => $by->id,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['infractions'] ?? [] as $row) {
                $this->infractions->record(
                    $subject,
                    InfractionType::findOrFail($row['infraction_type_id']),
                    [
                        'occurred_on' => $manhunt->started_at->toDateString(),
                        'location' => $row['location'] ?? $manhunt->district,
                        'disposition' => $row['disposition'] ?? null,
                    ],
                    $by,
                    $manhunt,
                    false,
                );
            }

            $this->applyOutcome($subject, $manhunt, $by);
            $this->stats->refresh($subject);

            return $manhunt;
        });
    }

    public function delete(Manhunt $manhunt): void
    {
        $subject = $manhunt->subject()->withTrashed()->firstOrFail();

        $manhunt->delete();

        $this->stats->refresh($subject);
    }

    /** Apprehended puts the subject in custody. Evaded makes them wanted. */
    private function applyOutcome(Subject $subject, Manhunt $manhunt, User $by): void
    {
        $target = $manhunt->outcome === ManhuntOutcome::Apprehended
            ? SubjectStatus::InCustody
            : SubjectStatus::Wanted;

        if ($subject->status !== $target) {
            $this->status->change($subject, $target, "Manhunt {$manhunt->code}: {$manhunt->outcome->label()}", $by);
        }
    }

    private function newCode(): string
    {
        do {
            $code = 'MH-'.strtoupper(Str::random(8));
        } while (Manhunt::where('code', $code)->exists());

        return $code;
    }
}
