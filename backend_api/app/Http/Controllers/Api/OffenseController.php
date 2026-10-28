<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOffenseRequest;
use App\Http\Resources\OffenseResource;
use App\Models\AuditLog;
use App\Models\Offense;
use App\Models\Subject;
use App\Services\OffenseClassifier;
use App\Services\RiskScoreService;
use App\Services\UkPoliceService;
use Illuminate\Http\Request;

class OffenseController extends Controller
{
    public function forSubject(Request $request, Subject $subject)
    {
        $this->authorize('view', $subject);
        return OffenseResource::collection($subject->offenses()->orderBy($request->query('sort') === 'date' ? 'occurred_on' : 'id', 'desc')->paginate(50));
    }

    public function store(StoreOffenseRequest $request, Subject $subject, OffenseClassifier $classifier, RiskScoreService $risk)
    {
        $this->authorize('update', $subject);
        $data = $request->validated();
        $offense = $subject->offenses()->create([
            ...$data,
            'charge' => $classifier->charge($data['category']),
            'severity' => $risk->severity($data['category']),
            'status_effect' => $classifier->statusEffect($data['disposition'] ?? null),
        ]);
        $risk->recalculate($subject);
        AuditLog::create(['user_id' => $request->user()->id, 'subject_id' => $subject->id, 'action' => 'offense_added', 'meta' => ['offense_id' => $offense->id]]);
        return (new OffenseResource($offense))->response()->setStatusCode(201);
    }

    public function index(Request $request)
    {
        abort_unless((int) $request->user()->clearance_level >= 3, 403);
        $query = Offense::with('subject:id,case_id,name,alias')->when($request->query('category'), fn ($q, $category) => $q->where('category', $category));
        return OffenseResource::collection($query->orderBy('occurred_on', $request->query('sort') === 'oldest' ? 'asc' : 'desc')->paginate(min(100, max(1, (int) $request->query('per_page', 25))))->withQueryString());
    }

    public function destroy(Request $request, Offense $offense, RiskScoreService $risk)
    {
        abort_unless((int) $request->user()->clearance_level >= 3, 403);
        $subject = $offense->subject;
        $this->authorize('update', $subject);
        $offense->delete();
        $risk->recalculate($subject);
        AuditLog::create(['user_id' => $request->user()->id, 'subject_id' => $subject->id, 'action' => 'updated', 'meta' => ['offense_deleted' => true]]);
        return response()->json(['message' => 'Offense removed.']);
    }

    public function categories(UkPoliceService $police)
    {
        $categories = $police->categories();
        if ($categories === []) $categories = collect(['violent-crime', 'robbery', 'possession-of-weapons', 'burglary', 'drugs', 'criminal-damage-arson', 'vehicle-crime', 'theft-from-the-person', 'other-theft', 'shoplifting', 'public-order', 'bicycle-theft', 'anti-social-behaviour', 'other-crime'])->map(fn ($slug) => ['url' => $slug, 'name' => str($slug)->replace('-', ' ')->title()->toString()])->all();
        return response()->json(['data' => $categories]);
    }
}
