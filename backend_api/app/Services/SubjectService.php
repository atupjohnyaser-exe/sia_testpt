<?php

namespace App\Services;

use App\Enums\SubjectStatus;
use App\Models\StatusChange;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubjectService
{
    public function __construct(private SubjectPhotoService $photos)
    {
    }

    public function create(array $data, SubjectStatus $status, User $by): Subject
    {
        return DB::transaction(function () use ($data, $status, $by) {
            $subject = new Subject(Arr::except($data, ['status', 'case_id']));
            $subject->case_id = $this->newCaseId();
            $subject->forceFill(['status' => $status, 'threat_level' => 1]);

            if (! empty($data['officer_advisory'])) {
                $subject->advisory_reviewed_by = $by->id;
                $subject->advisory_reviewed_at = now();
            }

            $subject->save();

            StatusChange::create([
                'subject_id' => $subject->id,
                'from_status' => null,
                'to_status' => $status,
                'reason' => 'Record created',
                'changed_by' => $by->id,
                'created_at' => now(),
            ]);

            return $subject;
        });
    }

    /** Profile fields only. Status, bounty and threat level are not editable here. */
    public function update(Subject $subject, array $data, User $by): Subject
    {
        $subject->fill($data);

        // Pasting an outside link replaces an uploaded portrait.
        if (! empty($data['photo_url']) && $subject->photo_path) {
            $this->photos->forget($subject);
        }

        if ($subject->isDirty('officer_advisory') && $subject->officer_advisory !== null) {
            $subject->advisory_reviewed_by = $by->id;
            $subject->advisory_reviewed_at = now();
        }

        $subject->save();

        return $subject;
    }

    private function newCaseId(): string
    {
        do {
            $caseId = 'RP-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT).'-'.Str::upper(Str::random(2));
        } while (Subject::withTrashed()->where('case_id', $caseId)->exists());

        return $caseId;
    }
}
