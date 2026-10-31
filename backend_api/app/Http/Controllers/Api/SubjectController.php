<?php

namespace App\Http\Controllers\Api;

use App\Enums\AuditAction;
use App\Enums\SubjectStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSubjectRequest;
use App\Http\Requests\UpdateSubjectRequest;
use App\Http\Resources\SubjectResource;
use App\Models\Subject;
use App\Services\AuditLogger;
use App\Services\SubjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubjectController extends Controller
{
    public function __construct(private SubjectService $subjects)
    {
    }

    public function index(Request $request)
    {
        $request->validate([
            'status' => ['nullable', Rule::enum(SubjectStatus::class)],
            'threat_level' => ['nullable', 'integer', 'between:1,6'],
            'sort' => ['nullable', Rule::in(['bounty', 'threat', 'name', 'recent'])],
            'search' => ['nullable', 'string', 'max:100'],
        ] + $this->pagingRules());

        $query = Subject::query();

        $search = trim((string) $request->query('search', ''));

        if ($search !== '') {
            $like = '%'.addcslashes($search, '%_\\').'%';

            $query->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('alias', 'like', $like)
                    ->orWhere('case_id', 'like', $like);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('threat_level')) {
            $query->where('threat_level', (int) $request->query('threat_level'));
        }

        match ($request->query('sort', 'bounty')) {
            'name' => $query->orderBy('name'),
            'recent' => $query->latest('id'),
            'threat' => $query->orderByDesc('threat_level')->orderByDesc('bounty'),
            default => $query->orderByDesc('bounty')->orderBy('id'),
        };

        return SubjectResource::collection($query->paginate($this->perPage($request))->withQueryString());
    }

    public function store(StoreSubjectRequest $request): JsonResponse
    {
        $status = SubjectStatus::from($request->validated('status') ?? SubjectStatus::Wanted->value);

        $subject = $this->subjects->create($request->validated(), $status, $request->user());

        return (new SubjectResource($subject->refresh()))->response()->setStatusCode(201);
    }

    public function show(Request $request, Subject $subject, AuditLogger $audit): SubjectResource
    {
        $audit->recordView($subject, $request->user());

        $subject->load([
            'advisoryReviewer',
            'infractions' => fn ($query) => $query->with('type')->latest('occurred_on')->latest('id')->limit(5),
        ]);

        return new SubjectResource($subject);
    }

    public function update(UpdateSubjectRequest $request, Subject $subject): SubjectResource
    {
        return new SubjectResource($this->subjects->update($subject, $request->validated(), $request->user()));
    }

    /** Soft delete. The record stays in the database and a supervisor can restore it. */
    public function destroy(Subject $subject): JsonResponse
    {
        $subject->delete();

        return response()->json(['message' => 'Record archived.']);
    }

    public function restore(Subject $subject): SubjectResource
    {
        $subject->restore();

        return new SubjectResource($subject);
    }

    /** Streams every record as one JSON array, in chunks, and writes an audit row first. */
    public function export(Request $request, AuditLogger $audit): StreamedResponse
    {
        $audit->record(AuditAction::Exported, null, ['scope' => 'subjects']);

        return response()->streamDownload(function () use ($request) {
            echo '[';
            $first = true;

            foreach (Subject::with('advisoryReviewer')->lazyById(200) as $subject) {
                $row = (new SubjectResource($subject))->resolve($request);

                // A signed image link would keep working after the export is saved, so leave it out.
                if (($row['photo_source'] ?? null) === 'upload') {
                    $row['photo_url'] = null;
                }

                echo ($first ? '' : ',').json_encode($row);
                $first = false;
            }

            echo ']';
        }, 'rockport-subjects.json', ['Content-Type' => 'application/json']);
    }
}
