<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNoteRequest;
use App\Http\Resources\NoteResource;
use App\Models\AuditLog;
use App\Models\CaseNote;
use App\Models\Subject;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    public function index(Request $request, Subject $subject)
    {
        $this->authorize('view', $subject);
        return NoteResource::collection($subject->notes()->with('user')->latest()->paginate(30));
    }

    public function store(StoreNoteRequest $request, Subject $subject)
    {
        $this->authorize('update', $subject);
        $note = $subject->notes()->create(['user_id' => $request->user()->id, 'body' => $request->validated('body'), 'access_level' => 3]);
        AuditLog::create(['user_id' => $request->user()->id, 'subject_id' => $subject->id, 'action' => 'note_added', 'meta' => ['note_id' => $note->id]]);
        return (new NoteResource($note->load('user')))->response()->setStatusCode(201);
    }

    public function destroy(Request $request, CaseNote $note)
    {
        abort_unless((int) $request->user()->clearance_level >= 4 || $note->user_id === $request->user()->id, 403);
        $note->delete();
        AuditLog::create(['user_id' => $request->user()->id, 'subject_id' => $note->subject_id, 'action' => 'updated', 'meta' => ['note_deleted' => true]]);
        return response()->json(['message' => 'Note removed.']);
    }
}
