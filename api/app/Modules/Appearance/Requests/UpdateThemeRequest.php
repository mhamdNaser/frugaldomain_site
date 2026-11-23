<?php

namespace App\Modules\Appearance\Requests;

use App\Modules\Appearance\Support\AppearanceDefaults;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateThemeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // role:admin middleware guards the route
    }

    public function rules(): array
    {
        $colour = ['sometimes', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'];
        $url = ['sometimes', 'nullable', 'string', 'max:2048', 'regex:#^(https?://|/(?!/))#'];
        $rules = [
            'logo_url' => $url, 'logo_dark_url' => $url, 'favicon_url' => $url,
            'font_body' => ['sometimes', Rule::in(AppearanceDefaults::FONTS)],
            'font_heading' => ['sometimes', Rule::in(AppearanceDefaults::FONTS)],
            'radius' => ['sometimes', 'integer', 'between:0,32'],
            'default_mode' => ['sometimes', Rule::in(['dark', 'light'])],
            'dark' => ['sometimes', 'array'],
            'light' => ['sometimes', 'array'],
        ];
        foreach (['dark', 'light'] as $mode) {
            foreach (array_keys(AppearanceDefaults::theme()[$mode]) as $key) {
                $rules["$mode.$key"] = $colour;
            }
        }

        return $rules;
    }
}
