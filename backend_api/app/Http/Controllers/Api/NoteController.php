<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNoteRequest;
use App\Http\Resources\NoteResource;
use App\Models\CaseNote;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    public function index(Request $request, Subject $subject)
    {
        $request->validate($this->pagingRules());

        return NoteResource::collection(
            $subject->notes()->with('author:id,name,badge_id,initials')->latest('id')->paginate($this->perPage($request, 30))
        );
    }

    public function store(StoreNoteRequest $request, Subject $subject): JsonResponse
    {
        $note = $subject->notes()->create([
            'user_id' => $request->user()->id,
            'body' => $request->validated('body'),
            'access_level' => $request->validated('access_level') ?? 3,
        ]);

        return (new NoteResource($note->load('author:id,name,badge_id,initials')))->response()->setStatusCode(201);
    }

    public function destroy(CaseNote $note): JsonResponse
    {
        $this->authorize('delete', $note);

        $note->delete();

        return response()->json(['message' => 'Note deleted.']);
    }
}
