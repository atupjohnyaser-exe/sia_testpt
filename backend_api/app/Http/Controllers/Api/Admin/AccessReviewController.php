<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\Clearance;
use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveAccessRequestRequest;
use App\Http\Requests\RejectAccessRequestRequest;
use App\Http\Resources\AccessRequestResource;
use App\Models\AccessRequest;
use App\Models\Subject;
use App\Models\User;
use App\Services\AccessRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Chief Admin only. Reviewing applications for access. */
class AccessReviewController extends Controller
{
    public function summary(): JsonResponse
    {
        $byLevel = User::query()->where('is_active', true)->selectRaw('clearance_level, COUNT(*) AS total')->groupBy('clearance_level')->pluck('total', 'clearance_level');

        return response()->json(['data' => [
            'pending_requests' => AccessRequest::query()->where('status', AccessRequest::PENDING)->count(),
            'active_personnel' => User::query()->where('is_active', true)->count(),
            'inactive_personnel' => User::query()->where('is_active', false)->count(),
            'archived_records' => Subject::onlyTrashed()->count(),
            'by_level' => collect(Clearance::cases())->map(fn (Clearance $level) => [
                'level' => $level->value,
                'label' => $level->label(),
                'total' => (int) ($byLevel[$level->value] ?? 0),
            ])->values(),
        ]]);
    }

    public function index(Request $request)
    {
        $request->validate([
            'status' => ['nullable', Rule::in([AccessRequest::PENDING, AccessRequest::APPROVED, AccessRequest::REJECTED])],
        ] + $this->pagingRules());

        $query = AccessRequest::query()->with(['reviewer:id,name', 'user:id,badge_id'])->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        return AccessRequestResource::collection($query->paginate($this->perPage($request, 20))->withQueryString());
    }

    public function approve(ApproveAccessRequestRequest $request, AccessRequest $accessRequest, AccessRequestService $service): JsonResponse
    {
        $result = $service->approve(
            $accessRequest,
            $request->user(),
            $request->filled('level') ? (int) $request->input('level') : null,
            $request->input('password'),
            $request->input('note'),
        );

        return response()->json([
            'data' => new AccessRequestResource($result['request']->load(['reviewer:id,name', 'user:id,badge_id'])),
            // Shown once. The password is not stored in a form anyone can read back.
            'credentials' => [
                'badge_id' => $result['user']->badge_id,
                'password' => $result['password'],
                'name' => $result['user']->name,
                'rank' => $result['user']->rank,
                'level' => $result['user']->clearance_level,
            ],
        ]);
    }

    public function reject(RejectAccessRequestRequest $request, AccessRequest $accessRequest, AccessRequestService $service): AccessRequestResource
    {
        return new AccessRequestResource(
            $service->reject($accessRequest, $request->user(), $request->input('note'))->load(['reviewer:id,name'])
        );
    }
}
