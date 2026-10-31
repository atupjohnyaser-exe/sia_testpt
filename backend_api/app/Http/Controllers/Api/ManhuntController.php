<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreManhuntRequest;
use App\Http\Resources\ManhuntResource;
use App\Models\Manhunt;
use App\Models\Subject;
use App\Services\ManhuntService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ManhuntController extends Controller
{
    public function __construct(private ManhuntService $manhunts)
    {
    }

    /** ?sort=bounty|recent|length (default bounty). ?top=5 returns just the top five, no paging. */
    public function forSubject(Request $request, Subject $subject)
    {
        $request->validate([
            'sort' => ['nullable', Rule::in(['bounty', 'recent', 'length'])],
            'top' => ['nullable', 'integer', 'between:1,25'],
        ] + $this->pagingRules());

        $query = $subject->manhunts()->withCount('infractions');

        match ($request->query('sort', 'bounty')) {
            'recent' => $query->orderByDesc('started_at'),
            'length' => $query->orderByDesc('duration_seconds'),
            default => $query->orderByDesc('bounty_value'),
        };

        $top = (int) $request->query('top', 0);

        if ($top > 0) {
            return ManhuntResource::collection($query->limit(min($top, 25))->get());
        }

        return ManhuntResource::collection($query->paginate($this->perPage($request))->withQueryString());
    }

    public function index(Request $request)
    {
        $request->validate($this->pagingRules());

        return ManhuntResource::collection(
            Manhunt::with('subject:id,case_id,name,alias')
                ->withCount('infractions')
                ->orderByDesc('started_at')
                ->paginate($this->perPage($request))
        );
    }

    public function show(Manhunt $manhunt): ManhuntResource
    {
        $manhunt->load(['subject:id,case_id,name,alias', 'infractions.type'])->loadCount('infractions');

        return new ManhuntResource($manhunt);
    }

    public function store(StoreManhuntRequest $request, Subject $subject): JsonResponse
    {
        $manhunt = $this->manhunts->log($subject, $request->validated(), $request->user());

        $manhunt->load('infractions.type')->loadCount('infractions');

        return (new ManhuntResource($manhunt))->response()->setStatusCode(201);
    }

    public function destroy(Manhunt $manhunt): JsonResponse
    {
        $this->manhunts->delete($manhunt);

        return response()->json(['message' => 'Manhunt removed.']);
    }
}
