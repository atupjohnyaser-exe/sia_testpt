<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSubjectRequest;
use App\Http\Requests\UpdateSubjectRequest;
use App\Http\Resources\SubjectResource;
use App\Models\AuditLog;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SubjectController extends Controller
{
    public function index(Request $request)
    {
        abort_unless((int) $request->user()->clearance_level >= 3, 403);
        $subjects = Subject::query()->withCount('offenses');
        if ($search = trim($request->string('search')->toString())) {
            $subjects->where(fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('alias', 'like', "%{$search}%")->orWhere('case_id', 'like', "%{$search}%"));
        }
        if ($status = $request->query('status')) $subjects->where('status', $status);
        match ($request->query('sort', 'risk')) {
            'name' => $subjects->orderBy('name'), 'recent' => $subjects->latest(), default => $subjects->orderByDesc('risk_score'),
        };
        return SubjectResource::collection($subjects->paginate(min(100, max(1, (int) $request->query('per_page', 20))))->withQueryString());
    }

    public function store(StoreSubjectRequest $request)
    {
        $data = $request->validated();
        $data['case_id'] ??= $this->caseId($data['name']);
        if (! array_key_exists('risk_override', $data)) $data['risk_override'] = true;
        $subject = Subject::create($data);
        AuditLog::create(['user_id' => $request->user()->id, 'subject_id' => $subject->id, 'action' => 'created']);
        return (new SubjectResource($subject->loadCount('offenses')))->response()->setStatusCode(201);
    }

    public function show(Request $request, Subject $subject)
    {
        $this->authorize('view', $subject);
        AuditLog::create(['user_id' => $request->user()->id, 'subject_id' => $subject->id, 'action' => 'viewed']);
        return new SubjectResource($subject->loadCount('offenses')->load(['advisoryReviewer', 'offenses' => fn ($query) => $query->latest('occurred_on')->limit(3)]));
    }

    public function update(UpdateSubjectRequest $request, Subject $subject)
    {
        $this->authorize('update', $subject);
        $data = $request->validated();
        if (array_key_exists('risk_score', $data) && ! array_key_exists('risk_override', $data)) $data['risk_override'] = true;
        $subject->update($data);
        AuditLog::create(['user_id' => $request->user()->id, 'subject_id' => $subject->id, 'action' => 'updated']);
        return new SubjectResource($subject->refresh()->loadCount('offenses'));
    }

    public function destroy(Request $request, Subject $subject)
    {
        $this->authorize('delete', $subject);
        $subject->delete();
        AuditLog::create(['user_id' => $request->user()->id, 'subject_id' => $subject->id, 'action' => 'deleted']);
        return response()->json(['message' => 'Record archived.']);
    }

    public function restore(Request $request, int $subject)
    {
        abort_unless((int) $request->user()->clearance_level >= 3, 403);
        $record = Subject::withTrashed()->findOrFail($subject);
        $record->restore();
        AuditLog::create(['user_id' => $request->user()->id, 'subject_id' => $record->id, 'action' => 'updated', 'meta' => ['restored' => true]]);
        return new SubjectResource($record->loadCount('offenses'));
    }

    public function export(Request $request)
    {
        abort_unless((int) $request->user()->clearance_level >= 3, 403);
        return SubjectResource::collection(Subject::with(['advisoryReviewer', 'offenses', 'notes.user'])->withCount('offenses')->get());
    }

    private function caseId(string $name): string
    {
        do { $id = 'RP-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT).'-'.Str::upper(Str::random(2)); }
        while (Subject::withTrashed()->where('case_id', $id)->exists());
        return $id;
    }
}
