<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadPhotoRequest;
use App\Http\Resources\SubjectResource;
use App\Models\Subject;
use App\Services\SubjectPhotoService;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubjectPhotoController extends Controller
{
    /**
     * Sends the image. Not behind a login because an <img> tag cannot send a token,
     * so it is protected by a signed link that expires instead (see SubjectResource).
     */
    public function show(Subject $subject): StreamedResponse
    {
        $path = $subject->photo_path;

        abort_unless($path !== null && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, ['X-Content-Type-Options' => 'nosniff'], 'inline');
    }

    public function store(UploadPhotoRequest $request, Subject $subject, SubjectPhotoService $photos): SubjectResource
    {
        return new SubjectResource($photos->store($subject, $request->file('photo'))->refresh());
    }

    public function destroy(Subject $subject, SubjectPhotoService $photos): SubjectResource
    {
        return new SubjectResource($photos->remove($subject)->refresh());
    }
}
