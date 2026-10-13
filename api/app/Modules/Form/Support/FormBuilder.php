<?php

namespace App\Modules\Form\Support;

use App\Modules\Form\Models\Form;
use App\Modules\Form\Models\FormField;
use Illuminate\Support\Facades\DB;

/**
 * Turning what the builder sent into the stored definition of a form.
 *
 * The hard part is not creating fields, it is editing a form that has already
 * collected answers. Three rules keep that safe:
 *
 *   - A key is generated once from the label and never changes. Rename the
 *     question all you like; the answers stay attached.
 *   - A field that already has answers keeps its type. Re-reading a date as a
 *     number would not convert the stored answers, it would just make them
 *     wrong.
 *   - Removing a field does not remove what people answered. The answer stays
 *     in the submission's JSON, so an export of older responses is still
 *     complete; only the index rows go, which is what releases a uniqueness
 *     claim the owner no longer wants enforced.
 */
class FormBuilder
{
    public static function save(Form $form, array $payload): Form
    {
        return DB::transaction(function () use ($form, $payload) {
            $form->fill([
                'title' => $payload['title'],
                'title_ar' => $payload['title_ar'] ?? null,
                'description' => $payload['description'] ?? null,
                'description_ar' => $payload['description_ar'] ?? null,
                'closes_at' => $payload['closes_at'] ?? null,
                'settings' => array_merge(
                    Form::DEFAULT_SETTINGS,
                    $form->settings ?? [],
                    $payload['settings'] ?? []
                ),
            ]);

            if (! empty($payload['status'])) {
                $form->status = $payload['status'];

                if ($payload['status'] === Form::STATUS_PUBLISHED) {
                    $form->published_at ??= now();
                }
            }

            $form->save();

            self::syncFields($form, $payload['fields'] ?? []);

            return $form->load('fields');
        });
    }

    private static function syncFields(Form $form, array $incoming): void
    {
        $existing = $form->fields()->get()->keyBy('id');
        $taken = $existing->pluck('key')->all();
        $keptIds = [];

        foreach (array_values($incoming) as $position => $input) {
            $id = $input['id'] ?? null;
            $current = $id ? $existing->get((int) $id) : null;

            /*
             * A question written only in Arabic still needs a label to show
             * wherever the English one would appear - the export header in
             * English, the validation message, the column of a csv - so the
             * Arabic stands in for it rather than leaving a blank.
             */
            $label = ($input['label'] ?? '') ?: ($input['label_ar'] ?? '');

            $attributes = [
                'label' => $label,
                'label_ar' => $input['label_ar'] ?? null,
                'placeholder' => $input['placeholder'] ?? null,
                'help' => $input['help'] ?? null,
                'help_ar' => $input['help_ar'] ?? null,
                'required' => (bool) ($input['required'] ?? false),
                'unique' => (bool) ($input['unique'] ?? false),
                'rules' => self::cleanRules($input['type'], $input['rules'] ?? []),
                'options' => self::cleanOptions($input['type'], $input['options'] ?? []),
                'default_value' => $input['default_value'] ?? null,
                'position' => $position,
            ];

            if ($current) {
                /*
                 * The type is only allowed to change while the field has no
                 * answers. Once it does, a change of type would reinterpret
                 * data that was collected under the old meaning.
                 */
                if ($current->type !== $input['type'] && ! self::hasAnswers($form, $current->key)) {
                    $attributes['type'] = $input['type'];
                }

                $current->update($attributes);
                $keptIds[] = $current->id;

                continue;
            }

            $key = FieldTypes::keyFrom($label, $taken);
            $taken[] = $key;

            $field = $form->fields()->create(array_merge($attributes, [
                'key' => $key,
                'type' => $input['type'],
            ]));

            $keptIds[] = $field->id;
        }

        /*
         * Second pass: a question that follows another was saved pointing at
         * that question's *position*, because a field created in this same
         * request had no key until a moment ago. Now that every field has one,
         * the pointer becomes the key - which survives reordering, renaming
         * and everything else a position does not.
         */
        self::resolveDependencies($form, $incoming, $keptIds);

        $removed = $existing->reject(fn (FormField $field) => in_array($field->id, $keptIds, true));

        if ($removed->isNotEmpty()) {
            self::forgetIndexedValues($form, $removed->pluck('key')->all());
            FormField::whereIn('id', $removed->pluck('id'))->delete();
        }
    }

    private static function resolveDependencies(Form $form, array $incoming, array $keptIds): void
    {
        $fields = FormField::whereIn('id', $keptIds)->orderBy('position')->get();
        $keyAt = $fields->pluck('key')->values();

        foreach ($fields as $position => $field) {
            $rules = $field->rules ?? [];
            $pointer = $rules['depends_on'] ?? null;

            if ($pointer === null || $pointer === '') {
                continue;
            }

            $parentKey = $keyAt[(int) $pointer] ?? null;

            // A pointer at a question that no longer exists is dropped rather
            // than kept as a rule nothing can satisfy.
            if ($parentKey === null || $parentKey === $field->key) {
                unset($rules['depends_on']);
            } else {
                $rules['depends_on'] = $parentKey;
            }

            $field->rules = $rules;
            $field->save();
        }
    }

    /** Keep only the settings that mean something for this type. */
    private static function cleanRules(string $type, array $rules): array
    {
        $allowed = FieldTypes::TYPES[$type]['settings'] ?? [];

        return collect($rules)
            ->only($allowed)
            ->reject(fn ($value) => $value === null || $value === '')
            ->all();
    }

    private static function cleanOptions(string $type, array $options): array
    {
        if (empty(FieldTypes::TYPES[$type]['options'] ?? false)) {
            return [];
        }

        $clean = [];

        foreach ($options as $option) {
            $value = (string) ($option['value'] ?? '');

            if ($value === '') {
                continue;
            }

            $when = array_values(array_unique(array_map('strval', (array) ($option['when'] ?? []))));

            /*
             * The same option can belong to more than one answer above - a
             * town listed under two areas - so a repeat merges its `when`
             * rather than replacing the first, which would silently drop one
             * of the two places it should appear.
             */
            if (isset($clean[$value])) {
                $clean[$value]['when'] = array_values(array_unique(array_merge($clean[$value]['when'], $when)));

                continue;
            }

            $clean[$value] = [
                'value' => $value,
                'label' => (string) ($option['label'] ?? $value),
                'label_ar' => $option['label_ar'] ?? null,
                'when' => $when,
            ];
        }

        return array_values($clean);
    }

    private static function hasAnswers(Form $form, string $key): bool
    {
        return DB::connection('forms')
            ->table('form_submission_values')
            ->where('form_id', $form->id)
            ->where('field_key', $key)
            ->exists();
    }

    /**
     * Drop the index rows of fields the owner removed.
     *
     * The answers themselves stay in the submissions' JSON. What goes is the
     * index - which is also the uniqueness claim, so deleting a unique field
     * genuinely stops it being enforced rather than leaving an invisible
     * constraint behind.
     */
    private static function forgetIndexedValues(Form $form, array $keys): void
    {
        if (! $keys) {
            return;
        }

        DB::connection('forms')
            ->table('form_submission_values')
            ->where('form_id', $form->id)
            ->whereIn('field_key', $keys)
            ->delete();
    }
}
