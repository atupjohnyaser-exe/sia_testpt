<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadPhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * JPG, PNG or WebP only (no SVG, which can carry scripts), up to 2 MB,
     * between 100 and 3000 pixels on each side. The type is checked from the file
     * contents, not from the file name.
     */
    public function rules(): array
    {
        return [
            'photo' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'mimetypes:image/jpeg,image/png,image/webp',
                'max:2048',
                'dimensions:min_width=100,min_height=100,max_width=3000,max_height=3000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'photo.image' => 'The file must be a real image.',
            'photo.mimes' => 'Only JPG, PNG and WebP images are accepted.',
            'photo.mimetypes' => 'Only JPG, PNG and WebP images are accepted.',
            'photo.max' => 'The image must be 2 MB or smaller.',
            'photo.dimensions' => 'The image must be between 100 and 3000 pixels on each side.',
            'photo.uploaded' => 'The upload failed. The file may be larger than the server allows.',
        ];
    }
}
