<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInfractionRequest;
use App\Http\Requests\UpdateInfractionRequest;
use App\Http\Resources\InfractionResource;
use App\Models\Infraction;
use App\Models\InfractionType;
use App\Models\Subject;
use App\Services\InfractionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InfractionController extends Controller
{
    public function __construct(private InfractionService $infractions)
    {
    }

    /** All infractions on one subject. Filter with ?served=true or ?served=false. */
    public function forSubject(Request $request, Subject $subject)
    {
        $query = $subject->infractions()->with(['type', 'manhunt:id,code'])->latest('occurred_on')->latest('id');

        if ($request->has('served')) {
            $request->boolean('served') ? $query->whereNotNull('served_at') : $query->whereNull('served_at');
        }

        return InfractionResource::collection($query->paginate($this->perPage($request, 25))->withQueryString());
    }

    /** All infractions across subjects. Filter with ?type=code and ?served=. */
    public function index(Request $request)
    {
        $query = Infraction::query()
            ->with(['subject:id,case_id,name', 'type', 'manhunt:id,code'])
            ->latest('occurred_on')
            ->latest('id');

        if ($request->filled('type')) {
            $code = (string) $request->query('type');
            $query->whereHas('type', fn ($q) => $q->where('code', $code));
        }

        if ($request->has('served')) {
            $request->boolean('served') ? $query->whereNotNull('served_at') : $query->whereNull('served_at');
        }

        return InfractionResource::collection($query->paginate($this->perPage($request, 25))->withQueryString());
    }

    public function store(StoreInfractionRequest $request, Subject $subject): JsonResponse
    {
        $infraction = $this->infractions->record(
            $subject,
            InfractionType::findOrFail($request->validated('infraction_type_id')),
            $request->validated(),
            $request->user(),
        );

        return (new InfractionResource($infraction->load('type')))->response()->setStatusCode(201);
    }

    /** Mark an infraction served or its fine paid. */
    public function update(UpdateInfractionRequest $request, Infraction $infraction): InfractionResource
    {
        $data = $request->validated();

        foreach (['served', 'fine_paid'] as $flag) {
            if (array_key_exists($flag, $data)) {
                $data[$flag] = $request->boolean($flag);
            }
        }

        $infraction = $this->infractions->update($infraction, $data, $request->user());

        return new InfractionResource($infraction->load('type'));
    }

    public function destroy(Infraction $infraction): JsonResponse
    {
        $this->infractions->delete($infraction);

        return response()->json(['message' => 'Infraction removed.']);
    }
}
