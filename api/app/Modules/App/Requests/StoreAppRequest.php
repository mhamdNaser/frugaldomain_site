<?php

namespace App\Modules\App\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAppRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // On update the route model id must be excluded from the unique check.
        $id = $this->route('id');

        return [
            // The slug becomes a directory name under public/apps/, so it is
            // constrained to a strict lowercase-kebab pattern. That single
            // rule is what makes "../" and absolute paths impossible.
            'slug' => [
                'required',
                'string',
                'max:120',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('apps', 'slug')->ignore($id)->whereNull('deleted_at'),
            ],
            'name' => 'required|string|max:255',
            'tagline' => 'nullable|string|max:500',
            'summary' => 'nullable|string',

            'type' => ['nullable', 'string', Rule::in(['react', 'static', 'vue', 'other'])],
            'status' => ['nullable', 'string', Rule::in(['draft', 'published'])],
            'version' => 'nullable|string|max:40',
            'updated_on' => 'nullable|date',
            'accent' => ['nullable', 'string', 'regex:/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],

            'tags' => 'nullable|array',
            'tags.*' => 'string|max:80',
            'stack' => 'nullable|array',
            'stack.*' => 'string|max:120',

            // Unbounded list of highlights. Accepts plain strings or
            // {label: "..."} objects; the repository normalises both.
            'features' => 'nullable|array',
            'features.*' => 'required',

            'docs' => 'nullable|array',
            'docs.*.id' => 'nullable|string|max:255',
            'docs.*.title' => 'nullable|string|max:255',
            'docs.*.body' => 'nullable|string',
            'docs.*.steps' => 'nullable|array',
            'docs.*.steps.*' => 'string',

            'repository' => 'nullable|url|max:500',

            'preview_mode' => ['nullable', 'string', Rule::in(['subfolder', 'subdomain', 'external'])],
            'preview_url' => 'nullable|string|max:500',
            'subdomain' => 'nullable|string|max:255',
            'preview_embed' => 'nullable|boolean',
            'preview_open_in_new_tab' => 'nullable|boolean',

            'ordering' => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'slug.regex' => 'The slug may only contain lowercase letters, digits and single hyphens.',
            'accent.regex' => 'The accent colour must be a hex value such as #38bdf8.',
        ];
    }

    /**
     * Multipart and query-string clients send booleans as "1"/"true"/"on";
     * normalise them before validation so `boolean` does not reject them.
     */
    protected function prepareForValidation(): void
    {
        foreach (['preview_embed', 'preview_open_in_new_tab'] as $key) {
            if ($this->has($key)) {
                $this->merge([$key => filter_var($this->input($key), FILTER_VALIDATE_BOOLEAN)]);
            }
        }
    }
}
