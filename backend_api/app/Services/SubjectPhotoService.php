<?php

namespace App\Services;

use App\Models\Subject;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Stores uploaded portraits on the private disk.
 *
 *  - The file name is random. The name the client sent is never used.
 *  - The extension comes from the real file contents, not from the file name.
 *  - When the GD extension is on, the image is decoded and saved again. That strips
 *    camera data (location, device) and removes anything hidden inside the file.
 *  - Files are never reachable by a public path. They are served through a signed link.
 */
class SubjectPhotoService
{
    private const DISK = 'local';

    private const EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public function store(Subject $subject, UploadedFile $file): Subject
    {
        $extension = self::EXTENSIONS[$file->getMimeType()] ?? null;

        if ($extension === null) {
            throw ValidationException::withMessages(['photo' => ['Only JPG, PNG and WebP images are accepted.']]);
        }

        $contents = $this->clean($file->get(), $extension);
        $path = 'subject-photos/'.Str::uuid()->toString().'.'.$extension;

        Storage::disk(self::DISK)->put($path, $contents);

        $previous = $subject->photo_path;

        $subject->forceFill(['photo_path' => $path, 'photo_url' => null])->save();

        $this->delete($previous);

        return $subject;
    }

    /** Removes the uploaded file and any outside link. */
    public function remove(Subject $subject): Subject
    {
        $previous = $subject->photo_path;

        $subject->forceFill(['photo_path' => null, 'photo_url' => null])->save();

        $this->delete($previous);

        return $subject;
    }

    /** Deletes the uploaded file and clears the field, without saving. For when an outside link replaces it. */
    public function forget(Subject $subject): void
    {
        $this->delete($subject->photo_path);

        $subject->photo_path = null;
    }

    private function delete(?string $path): void
    {
        if ($path) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    private function clean(string $bytes, string $extension): string
    {
        if (! function_exists('imagecreatefromstring')) {
            return $bytes;
        }

        $image = @imagecreatefromstring($bytes);

        if ($image === false) {
            throw ValidationException::withMessages(['photo' => ['That image could not be read. Try a different file.']]);
        }

        ob_start();

        $saved = match ($extension) {
            'jpg' => imagejpeg($image, null, 88),
            'png' => $this->savePng($image),
            'webp' => function_exists('imagewebp') && imagewebp($image, null, 85),
            default => false,
        };

        $encoded = ob_get_clean();

        // If this server cannot write that format, keep the original bytes (the checks above still passed).
        return ($saved && is_string($encoded) && $encoded !== '') ? $encoded : $bytes;
    }

    private function savePng($image): bool
    {
        imagealphablending($image, false);
        imagesavealpha($image, true);

        return imagepng($image);
    }
}
