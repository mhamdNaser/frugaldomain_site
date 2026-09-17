<?php

namespace App\Modules\App\Requests;

use App\Modules\App\Support\HostingerClient;
use Illuminate\Foundation\Http\FormRequest;

class ProvisionSubdomainRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // The same label rule the client enforces before it calls out, so
            // a bad prefix is a 422 here rather than a wasted Hostinger call.
            'prefix' => [
                'required',
                'string',
                'max:' . HostingerClient::PREFIX_MAX_LENGTH,
                'regex:' . HostingerClient::PREFIX_PATTERN,
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'prefix.regex' => 'The subdomain prefix may only contain lowercase letters, digits and inner hyphens, and must start and end with a letter or digit.',
        ];
    }

    /** Prefixes are case-insensitive; normalise before validating. */
    protected function prepareForValidation(): void
    {
        if ($this->has('prefix')) {
            $this->merge(['prefix' => strtolower(trim((string) $this->input('prefix')))]);
        }
    }
}
