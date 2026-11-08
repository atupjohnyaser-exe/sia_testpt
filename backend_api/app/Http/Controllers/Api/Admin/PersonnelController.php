<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\IssuePasswordRequest;
use App\Http\Requests\StorePersonnelRequest;
use App\Http\Requests\UpdatePersonnelRequest;
use App\Http\Resources\PersonnelResource;
use App\Models\User;
use App\Services\AccessRequestService;
use App\Services\PersonnelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Chief Admin only. The people who have access, and their personal information. */
class PersonnelController extends Controller
{
    public function __construct(private PersonnelService $personnel, private AccessRequestService $requests)
    {
    }

    public function index(Request $request)
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'level' => ['nullable', 'integer', 'between:1,5'],
            'active' => ['nullable', 'in:true,false,1,0'],
        ] + $this->pagingRules());

        $query = User::query()->with('approver:id,name')->orderByDesc('clearance_level')->orderBy('name');

        $search = trim((string) $request->query('search', ''));

        if ($search !== '') {
            $like = '%'.addcslashes($search, '%_\\').'%';
            $query->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('badge_id', 'like', $like)->orWhere('station', 'like', $like));
        }

        if ($request->filled('level')) {
            $query->where('clearance_level', (int) $request->query('level'));
        }

        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        return PersonnelResource::collection($query->paginate($this->perPage($request, 20))->withQueryString());
    }

    public function store(StorePersonnelRequest $request): JsonResponse
    {
        $result = $this->personnel->create($this->accountData($request->validated()), $request->user());

        return response()->json([
            'data' => new PersonnelResource($result['user']),
            'credentials' => $this->credentials($result['user'], $result['password']),
        ], 201);
    }

    public function update(UpdatePersonnelRequest $request, User $user): PersonnelResource
    {
        return new PersonnelResource($this->personnel->update($user, $this->accountData($request->validated()), $request->user()));
    }

    public function password(IssuePasswordRequest $request, User $user): JsonResponse
    {
        $password = $this->personnel->issuePassword($user, $request->input('password'), $request->user());

        return response()->json(['data' => new PersonnelResource($user->refresh()), 'credentials' => $this->credentials($user, $password)]);
    }

    public function activate(Request $request, User $user): PersonnelResource
    {
        return new PersonnelResource($this->personnel->setActive($user, true, $request->user()));
    }

    public function deactivate(Request $request, User $user): PersonnelResource
    {
        return new PersonnelResource($this->personnel->setActive($user, false, $request->user()));
    }

    /** The forms say full_name and send the address in pieces. The account stores name and one address line. */
    private function accountData(array $data): array
    {
        if (array_key_exists('full_name', $data)) {
            $data['name'] = $data['full_name'];
        }

        if (array_key_exists('address_municipality', $data)) {
            $data['address'] = $this->requests->address($data['address_street'] ?? null, $data['address_barangay'] ?? null, $data['address_municipality']);
        }

        return $data;
    }

    private function credentials(User $user, string $password): array
    {
        return ['badge_id' => $user->badge_id, 'password' => $password, 'name' => $user->name, 'rank' => $user->rank, 'level' => $user->clearance_level];
    }
}
