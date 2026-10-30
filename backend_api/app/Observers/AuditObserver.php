<?php

namespace App\Observers;

use App\Enums\AuditAction;
use App\Models\CaseNote;
use App\Models\Infraction;
use App\Models\Manhunt;
use App\Models\Sighting;
use App\Models\StatusChange;
use App\Models\Subject;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;

/**
 * One observer for every record type. Controllers never write audit rows
 * for model changes, so a new endpoint cannot forget to.
 */
class AuditObserver
{
    public function __construct(private AuditLogger $audit)
    {
    }

    public function created(Model $model): void
    {
        $this->write($model, 'created');
    }

    public function updated(Model $model): void
    {
        $this->write($model, 'updated');
    }

    public function deleted(Model $model): void
    {
        $this->write($model, 'deleted');
    }

    public function restored(Model $model): void
    {
        $this->write($model, 'restored');
    }

    private function write(Model $model, string $event): void
    {
        $action = $this->actionFor($model, $event);

        if ($action === null) {
            return;
        }

        $meta = $this->metaFor($model, $event);

        if ($meta === null) {
            return;
        }

        $subjectId = $model instanceof Subject ? $model->id : $model->subject_id;

        $this->audit->record($action, $subjectId, $meta);
    }

    private function actionFor(Model $model, string $event): ?AuditAction
    {
        return match (true) {
            $model instanceof Subject => match ($event) {
                'created' => AuditAction::Created,
                'updated' => AuditAction::Updated,
                'deleted' => AuditAction::Archived,
                'restored' => AuditAction::Restored,
                default => null,
            },
            $model instanceof Infraction => match ($event) {
                'created' => AuditAction::InfractionAdded,
                'updated' => $model->wasChanged('served_at') ? AuditAction::InfractionServed : AuditAction::Updated,
                'deleted' => AuditAction::InfractionDeleted,
                default => null,
            },
            $model instanceof Manhunt => match ($event) {
                'created' => AuditAction::ManhuntLogged,
                'deleted' => AuditAction::ManhuntDeleted,
                default => null,
            },
            $model instanceof CaseNote => match ($event) {
                'created' => AuditAction::NoteAdded,
                'deleted' => AuditAction::NoteDeleted,
                default => null,
            },
            $model instanceof Sighting => $event === 'created' ? AuditAction::SightingAdded : null,
            $model instanceof StatusChange => ($event === 'created' && $model->from_status !== null) ? AuditAction::StatusChanged : null,
            default => null,
        };
    }

    /**
     * What goes into the log row. Returns null when there is nothing worth logging.
     * Note text and other free text is never copied into the log.
     */
    private function metaFor(Model $model, string $event): ?array
    {
        $meta = match (true) {
            $model instanceof Subject => ['case_id' => $model->case_id],
            $model instanceof Infraction => ['infraction_id' => $model->id, 'type_id' => $model->infraction_type_id],
            $model instanceof Manhunt => ['manhunt' => $model->code, 'outcome' => $model->outcome?->value],
            $model instanceof CaseNote => ['note_id' => $model->id],
            $model instanceof Sighting => ['district' => $model->district],
            $model instanceof StatusChange => [
                'from' => $model->from_status?->value,
                'to' => $model->to_status->value,
                'reason' => $model->reason,
            ],
            default => [],
        };

        if ($event === 'updated') {
            $changed = array_values(array_diff(array_keys($model->getChanges()), ['updated_at', 'deleted_at']));

            if ($changed === []) {
                return null;
            }

            $meta['changed'] = $changed;
        }

        return $meta;
    }
}
