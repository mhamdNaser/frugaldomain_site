<?php

namespace App\Modules\Form\Requests;

use App\Modules\Form\Models\Form;
use App\Modules\Form\Support\FieldTypes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * What the builder is allowed to send when saving a form.
 *
 * Note what is absent: the key of a field. Keys are generated server-side and
 * frozen, so a renamed label cannot orphan answers already collected - and a
 * client cannot point a field at another field's stored answers.
 */
class SaveFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        $types = array_keys(FieldTypes::TYPES);

        return [
            'title' => 'required|string|max:200',
            'title_ar' => 'nullable|string|max:200',
            'description' => 'nullable|string|max:5000',
            'description_ar' => 'nullable|string|max:5000',

            'status' => ['nullable', Rule::in([Form::STATUS_DRAFT, Form::STATUS_PUBLISHED, Form::STATUS_CLOSED])],
            'closes_at' => 'nullable|date',

            'settings' => 'nullable|array',
            'settings.success_message' => 'nullable|string|max:1000',
            'settings.success_message_ar' => 'nullable|string|max:1000',
            'settings.allow_multiple_per_user' => 'nullable|boolean',
            'settings.require_login' => 'nullable|boolean',
            'settings.max_submissions' => 'nullable|integer|min:1|max:1000000',
            'settings.collect_email_notifications' => 'nullable|boolean',
            'settings.serial_label' => 'nullable|string|max:100',

            // A form with no fields is a page with a submit button, so the
            // builder is required to send at least one.
            'fields' => 'required|array|min:1|max:100',
            'fields.*.id' => 'nullable|integer',
            'fields.*.type' => ['required', 'string', Rule::in($types)],
            /*
             * One of the two is required, not both: a person building an
             * Arabic form types the question in Arabic and should not have to
             * invent an English version of it to be allowed to save.
             */
            'fields.*.label' => 'required_without:fields.*.label_ar|nullable|string|max:200',
            'fields.*.label_ar' => 'nullable|string|max:200',
            'fields.*.placeholder' => 'nullable|string|max:200',
            'fields.*.help' => 'nullable|string|max:300',
            'fields.*.help_ar' => 'nullable|string|max:300',
            'fields.*.required' => 'nullable|boolean',
            'fields.*.unique' => 'nullable|boolean',
            'fields.*.default_value' => 'nullable|string|max:500',

            // The per-field settings. Each is bounded, because every one of
            // them ends up in a validation rule that runs on every submission.
            'fields.*.rules' => 'nullable|array',
            'fields.*.rules.min_length' => 'nullable|integer|min:1|max:5000',
            'fields.*.rules.max_length' => 'nullable|integer|min:1|max:5000',
            'fields.*.rules.length' => 'nullable|integer|min:1|max:64',
            'fields.*.rules.min' => 'nullable|numeric',
            'fields.*.rules.max' => 'nullable|numeric',
            'fields.*.rules.integer' => 'nullable|boolean',
            'fields.*.rules.min_date' => 'nullable|date',
            'fields.*.rules.max_date' => 'nullable|date',
            'fields.*.rules.min_selected' => 'nullable|integer|min:1|max:100',
            'fields.*.rules.max_selected' => 'nullable|integer|min:1|max:100',
            'fields.*.rules.pattern' => 'nullable|string|max:200',
            // Kilobytes. Capped at 8 MB because the browser shrinks a photo
            // before sending it, and because PHP's own upload limit is the
            // real ceiling anyway.
            'fields.*.rules.max_size' => 'nullable|integer|min:32|max:8192',

            'fields.*.options' => 'nullable|array|max:200',
            'fields.*.options.*.value' => 'required_with:fields.*.options|string|max:200',
            'fields.*.options.*.label' => 'nullable|string|max:200',
            'fields.*.options.*.label_ar' => 'nullable|string|max:200',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            foreach ((array) $this->input('fields', []) as $i => $field) {
                $type = $field['type'] ?? null;
                $meta = FieldTypes::TYPES[$type] ?? null;

                if (! $meta) {
                    continue;
                }

                // A dropdown with no options is a dead end for whoever opens
                // the form, so it is caught here rather than at fill time.
                if (($meta['options'] ?? false) && empty($field['options'])) {
                    $validator->errors()->add("fields.{$i}.options", 'Add at least one option for this field.');
                }

                // "Digits of a fixed length" without the length is just "a
                // number", and the owner meant something specific - 13, say.
                if ($type === 'digits' && empty($field['rules']['length'])) {
                    $validator->errors()->add("fields.{$i}.rules.length", 'Set how many digits this field must have.');
                }

                $min = $field['rules']['min_length'] ?? null;
                $max = $field['rules']['max_length'] ?? null;
                if ($min && $max && (int) $min > (int) $max) {
                    $validator->errors()->add("fields.{$i}.rules.min_length", 'The minimum cannot be greater than the maximum.');
                }

                // A unique paragraph field is a trap: two people writing the
                // same sentence would block each other for no reason.
                if (! empty($field['unique']) && in_array($type, ['textarea', 'boolean', 'checkboxes', 'image'], true)) {
                    $validator->errors()->add("fields.{$i}.unique", 'This kind of field cannot be made unique.');
                }
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $fields = (array) $this->input('fields', []);

        foreach ($fields as $i => $field) {
            foreach (['required', 'unique'] as $flag) {
                if (array_key_exists($flag, $field)) {
                    $fields[$i][$flag] = filter_var($field[$flag], FILTER_VALIDATE_BOOLEAN);
                }
            }

            // Options arrive either as plain strings from a quick paste or as
            // objects from the builder; one shape reaches the validator.
            if (! empty($field['options']) && is_array($field['options'])) {
                $fields[$i]['options'] = array_values(array_map(
                    fn ($option) => is_array($option)
                        ? $option
                        : ['value' => (string) $option, 'label' => (string) $option],
                    $field['options']
                ));
            }
        }

        $this->merge(['fields' => $fields]);
    }
}
