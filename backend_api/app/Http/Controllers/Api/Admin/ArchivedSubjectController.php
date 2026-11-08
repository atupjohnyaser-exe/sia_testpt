<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubjectResource;
use App\Models\Subject;
use Illuminate\Http\Request;

/** Chief Admin only. Archived records, so they can be restored. */
class ArchivedSubjectController extends Controller
{
    public function index(Request $request)
    {
        $request->validate($this->pagingRules());

        return SubjectResource::collection(
            Subject::onlyTrashed()->latest('deleted_at')->paginate($this->perPage($request, 20))
        );
    }
}
