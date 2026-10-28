<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        abort_unless((int) $request->user()->clearance_level >= 3, 403);
        return AuditLog::with(['user:id,name,badge_id', 'subject:id,name,case_id'])
            ->when($request->query('subject_id'), fn ($q, $id) => $q->where('subject_id', $id))
            ->latest('created_at')->paginate(min(100, max(1, (int) $request->query('per_page', 25))));
    }
}
