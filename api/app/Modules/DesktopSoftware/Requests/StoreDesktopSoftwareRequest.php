<?php

namespace App\Modules\DesktopSoftware\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDesktopSoftwareRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');
        $slugRule = Rule::unique('desktop_software', 'slug');
        if ($id !== null) {
            $slugRule->ignore($id);
        }

        return [
            'slug' => [
                'required',
                'string',
                'max:120',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                $slugRule,
            ],
            'name' => 'required|string|max:255',
            'summary' => 'nullable|string',
            'readme' => 'required|string|max:1048576',
            // The other languages of the site; English is the columns above.
            'translations' => 'nullable|array:ar,hi,zh,tr',
            'translations.*' => 'nullable|array:name,summary,readme',
            'translations.*.name' => 'nullable|string|max:255',
            'translations.*.summary' => 'nullable|string',
            'translations.*.readme' => 'nullable|string|max:1048576',
            'platform' => ['required', 'string', Rule::in(['windows', 'linux', 'macos', 'other'])],
            'platform_name' => [
                Rule::requiredIf($this->input('platform') === 'other'),
                'nullable',
                'string',
                'max:120',
            ],
            'environment' => 'required|string|max:255',
            'programming_language' => 'required|string|max:120',
            'is_open_source' => 'required|boolean',
            'version' => 'nullable|string|max:40',
            'status' => ['required', 'string', Rule::in(['draft', 'published'])],
            'ordering' => 'nullable|integer|min:0',
        ];
    }
}
