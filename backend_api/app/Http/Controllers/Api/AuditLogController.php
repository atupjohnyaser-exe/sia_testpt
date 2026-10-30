<?php

namespace App\Http\Controllers\Api;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AuditLogController extends Controller
{
    /** Filters: user_id, subject_id, action, from, to (dates). */
    public function index(Request $request)
    {
        $request->validate([
            'user_id' => ['nullable', 'integer'],
            'subject_id' => ['nullable', 'integer'],
            'action' => ['nullable', Rule::enum(AuditAction::class)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $query = AuditLog::query()->with(['user:id,name,badge_id', 'subject:id,case_id,name'])->latest('id');

        foreach (['user_id', 'subject_id', 'action'] as $column) {
            if ($request->filled($column)) {
                $query->where($column, $request->query($column));
            }
        }

        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->date('from')->startOfDay());
        }

        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->date('to')->endOfDay());
        }

        return AuditLogResource::collection($query->paginate($this->perPage($request, 50))->withQueryString());
    }
}
