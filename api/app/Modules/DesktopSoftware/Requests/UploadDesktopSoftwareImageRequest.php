<?php

namespace App\Modules\DesktopSoftware\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadDesktopSoftwareImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'image' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:4096',
            ],
        ];
    }
}
