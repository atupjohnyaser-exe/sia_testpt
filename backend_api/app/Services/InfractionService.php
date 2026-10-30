<?php

namespace App\Services;

use App\Models\Infraction;
use App\Models\InfractionType;
use App\Models\Manhunt;
use App\Models\Subject;
use App\Models\User;

class InfractionService
{
    public function __construct(private SubjectStatsService $stats)
    {
    }

    /** The fine and bounty are copied from the type, so later price changes do not rewrite old records. */
    public function record(
        Subject $subject,
        InfractionType $type,
        array $data,
        User $by,
        ?Manhunt $manhunt = null,
        bool $refresh = true,
    ): Infraction {
        $infraction = Infraction::create([
            'subject_id' => $subject->id,
            'infraction_type_id' => $type->id,
            'manhunt_id' => $manhunt?->id,
            'occurred_on' => $data['occurred_on'],
            'location' => $data['location'],
            'disposition' => $data['disposition'] ?? null,
            'fine_amount' => $type->fine_amount,
            'bounty_value' => $type->bounty_value,
            'reported_by' => $by->id,
        ]);

        if ($refresh) {
            $this->stats->refresh($subject);
        }

        return $infraction;
    }

    /** Accepts 'served' and/or 'fine_paid' as booleans. */
    public function update(Infraction $infraction, array $data, User $by): Infraction
    {
        if (array_key_exists('served', $data)) {
            $infraction->served_at = $data['served'] ? ($infraction->served_at ?? now()) : null;
            $infraction->served_by = $data['served'] ? $by->id : null;
        }

        if (array_key_exists('fine_paid', $data)) {
            $infraction->fine_paid_at = $data['fine_paid'] ? ($infraction->fine_paid_at ?? now()) : null;
        }

        $infraction->save();

        $this->stats->refresh($infraction->subject()->withTrashed()->firstOrFail());

        return $infraction;
    }

    public function delete(Infraction $infraction): void
    {
        $subject = $infraction->subject()->withTrashed()->firstOrFail();

        $infraction->delete();

        $this->stats->refresh($subject);
    }
}
