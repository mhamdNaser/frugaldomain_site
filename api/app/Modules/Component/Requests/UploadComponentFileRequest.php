<?php

namespace App\Modules\Component\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadComponentFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // The extension is re-checked in the repository, which is the
            // authority; this keeps an obviously wrong upload from reaching it.
            'file' => ['required', 'file', 'max:2048', 'mimes:html,htm,jsx,tsx,vue,txt'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.max' => 'The template must be 2 MB or smaller.',
            'file.mimes' => 'Upload a .html, .jsx, .tsx or .vue file.',
        ];
    }
}
