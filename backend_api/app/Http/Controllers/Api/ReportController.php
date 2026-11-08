<?php

namespace App\Http\Controllers\Api;

use App\Enums\Clearance;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReportRequest;
use App\Http\Requests\UpdateReportRequest;
use App\Http\Resources\ReportResource;
use App\Models\Report;
use App\Support\Denied;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Field reports.
 *   Patrol officers (level 1) file reports, see and edit only their own, and cannot delete.
 *   Senior officers (level 2) and above see every report, can edit and review them.
 *   Only the Chief Admin deletes.
 */
class ReportController extends Controller
{
    private const WITH = ['filer:id,name,badge_id,rank', 'type', 'reviewer:id,name'];

    public function index(Request $request)
    {
        $request->validate([
            'status' => ['nullable', Rule::in([Report::FILED, Report::REVIEWED])],
            'mine' => ['nullable', 'in:true,false,1,0'],
            'municipality' => ['nullable', 'string', 'max:100'],
        ] + $this->pagingRules());

        $user = $request->user();
        $query = Report::query()->with(self::WITH)->latest('id');

        // Patrol officers only ever see their own.
        if (! $user->hasClearance(Clearance::Senior) || $request->boolean('mine')) {
            $query->where('filed_by', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('municipality')) {
            $query->where('municipality', $request->query('municipality'));
        }

        return ReportResource::collection($query->paginate($this->perPage($request, 15))->withQueryString());
    }

    public function store(StoreReportRequest $request): JsonResponse
    {
        $report = Report::create($request->validated() + [
            'reference' => $this->newReference(),
            'filed_by' => $request->user()->id,
        ]);

        // refresh() reads back what the database filled in, such as the FILED status.
        return (new ReportResource($report->refresh()->load(self::WITH)))->response()->setStatusCode(201);
    }

    public function show(Request $request, Report $report): ReportResource
    {
        $this->mustBeVisible($request, $report);

        return new ReportResource($report->load(self::WITH));
    }

    public function update(UpdateReportRequest $request, Report $report): ReportResource|JsonResponse
    {
        $this->mustBeVisible($request, $report);

        $user = $request->user();

        if (! $user->hasClearance(Clearance::Senior) && $report->status !== Report::FILED) {
            return Denied::response($user, Clearance::Senior, 'This report was already reviewed. A Senior Officer has to change it.');
        }

        $report->update($request->validated());

        return new ReportResource($report->refresh()->load(self::WITH));
    }

    public function review(Request $request, Report $report): ReportResource
    {
        if ($report->status !== Report::REVIEWED) {
            $report->forceFill([
                'status' => Report::REVIEWED,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ])->save();
        }

        return new ReportResource($report->refresh()->load(self::WITH));
    }

    public function destroy(Report $report): JsonResponse
    {
        $report->delete();

        return response()->json(['message' => 'Report deleted.']);
    }

    /** A patrol officer asking for someone else's report gets "not found", so its existence is not revealed. */
    private function mustBeVisible(Request $request, Report $report): void
    {
        $user = $request->user();

        abort_unless($user->hasClearance(Clearance::Senior) || $report->filed_by === $user->id, 404);
    }

    private function newReference(): string
    {
        do {
            $reference = 'BPD-R-'.now()->format('Y').'-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (Report::query()->where('reference', $reference)->exists());

        return $reference;
    }
}
