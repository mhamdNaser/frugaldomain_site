<?php

namespace App\Modules\App\Requests;

use App\Modules\App\Models\AppImage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadAppImageRequest extends FormRequest
{
    /** Ceiling for one screenshot, in kilobytes (4 MB). */
    public const MAX_KB = 4096;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // `file` + `mimes` rather than `image`, because `image` rejects
            // SVG while the catalogue accepts it.
            'image' => 'required|file|mimes:jpg,jpeg,png,webp,svg|max:' . self::MAX_KB,
            'role' => ['required', 'string', Rule::in([AppImage::ROLE_MAIN, AppImage::ROLE_SECONDARY])],
            // Which of the three secondary slots this upload fills.
            'ordering' => 'nullable|integer|min:0|max:' . (AppImage::SECONDARY_COUNT - 1),
            'alt' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'image.mimes' => 'Images must be jpg, jpeg, png, webp or svg.',
            'image.max' => 'An image may not be larger than 4 MB.',
        ];
    }
}
