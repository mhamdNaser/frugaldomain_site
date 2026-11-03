<?php

namespace App\Modules\DesktopSoftware\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadDesktopSoftwareFileRequest extends FormRequest
{
    public const MAX_KB = 204800;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:exe,zip,7z,tar,gz,msi,deb,rpm,appimage',
                'max:'.self::MAX_KB,
            ],
        ];
    }
}
