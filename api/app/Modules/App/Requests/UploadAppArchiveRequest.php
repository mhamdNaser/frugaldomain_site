<?php

namespace App\Modules\App\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadAppArchiveRequest extends FormRequest
{
    /** Ceiling for a build archive, in kilobytes (200 MB). */
    public const MAX_KB = 204800;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // `mimes:zip` checks the guessed extension against the detected
            // MIME type, so renaming a .php to .zip does not get through.
            'archive' => 'required|file|mimes:zip|max:' . self::MAX_KB,
        ];
    }

    public function messages(): array
    {
        return [
            'archive.mimes' => 'The project files must be a .zip archive.',
            'archive.max' => 'The archive may not be larger than 200 MB.',
        ];
    }
}
