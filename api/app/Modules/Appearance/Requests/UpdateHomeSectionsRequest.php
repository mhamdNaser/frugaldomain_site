<?php

namespace App\Modules\Appearance\Requests;

use App\Modules\Appearance\Support\AppearanceDefaults;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHomeSectionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // role:admin middleware guards the route
    }

    public function rules(): array
    {
        return [
            'sections' => ['present', 'array', 'max:40'],
            'sections.*.id' => ['required', 'string', 'max:64'],
            'sections.*.type' => ['required', Rule::in(AppearanceDefaults::SECTION_TYPES)],
            'sections.*.visible' => ['required', 'boolean'],
            'sections.*.props' => ['present', 'array'],
            'sections.*.i18n' => ['present', 'array'],
            'sections.*.i18n.*' => ['array'],
        ];
    }

    public function after(): array
    {
        return [function ($v) {
            foreach ((array) $this->input('sections') as $i => $s) {
                $bad = is_array($s['i18n'] ?? null) ? array_diff(array_keys($s['i18n']), AppearanceDefaults::LANGUAGES) : [];
                if ($bad) {
                    $v->errors()->add("sections.$i.i18n", 'Unknown language: '.implode(', ', $bad));
                }
            }
        }];
    }
}
