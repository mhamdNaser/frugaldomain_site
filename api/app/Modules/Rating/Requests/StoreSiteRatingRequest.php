<?php

namespace App\Modules\Rating\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSiteRatingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // A site path, never a URL: letters, digits, - _ and /.
            'path' => ['required', 'string', 'max:255', 'regex:#^/[A-Za-z0-9/_-]*$#'],
            'stars' => 'required|integer|between:1,5',
            'comment' => 'nullable|string|max:1000',
            'locale' => ['nullable', 'string', Rule::in(['en', 'ar', 'hi', 'zh', 'tr'])],
            // The random id the browser keeps (localStorage); only its HMAC is stored.
            'visitor' => ['required', 'string', 'min:16', 'max:64', 'regex:/^[A-Za-z0-9-]+$/'],
        ];
    }
}
