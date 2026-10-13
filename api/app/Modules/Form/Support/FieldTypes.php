<?php

namespace App\Modules\Form\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Every kind of field a form can hold, and what each one means.
 *
 * One registry, three readers: the API serves it to the builder so the owner
 * picks from the same list the server knows about, the validator turns a field
 * definition into Laravel rules, and the indexer decides which column of
 * form_submission_values an answer belongs in.
 *
 * The alternative - rules typed into the form definition as strings - puts the
 * server's validation in the hands of whoever edits a form, which is a hole,
 * and leaves the browser guessing at what the server will accept.
 *
 * Arabic is first class here. A "letters only" field that rejects Arabic is
 * not a letters-only field, and a national id of thirteen digits has to accept
 * the Arabic-Indic digits a phone keyboard produces, which is why answers are
 * normalised before they are judged.
 */
class FieldTypes
{
    /**
     * `settings` lists the knobs the builder shows for that type; the server
     * reads the same names out of the field's `rules` JSON.
     */
    public const TYPES = [
        'text' => [
            'label' => 'Short text',
            'group' => 'text',
            'settings' => ['min_length', 'max_length', 'pattern'],
            'indexes' => 'text',
        ],
        'letters' => [
            'label' => 'Letters only',
            'group' => 'text',
            'settings' => ['min_length', 'max_length'],
            'indexes' => 'text',
        ],
        'textarea' => [
            'label' => 'Paragraph',
            'group' => 'text',
            'settings' => ['min_length', 'max_length'],
            'indexes' => 'text',
        ],
        'email' => [
            'label' => 'Email',
            'group' => 'contact',
            'settings' => [],
            'indexes' => 'text',
        ],
        'phone' => [
            'label' => 'Phone',
            'group' => 'contact',
            'settings' => ['min_length', 'max_length'],
            'indexes' => 'text',
        ],
        'url' => [
            'label' => 'Link',
            'group' => 'contact',
            'settings' => [],
            'indexes' => 'text',
        ],
        'number' => [
            'label' => 'Number',
            'group' => 'number',
            'settings' => ['min', 'max', 'integer'],
            'indexes' => 'number',
        ],
        'digits' => [
            'label' => 'Digits of a fixed length',
            'group' => 'number',
            'settings' => ['length'],
            'indexes' => 'text',
            // A national id, an IBAN tail, a 13-digit code: these are not
            // numbers. Leading zeros matter and nobody adds them up, so they
            // are kept and indexed as text.
            'hint' => 'For identifiers such as a 13-digit national id.',
        ],
        'date' => [
            'label' => 'Date',
            'group' => 'time',
            'settings' => ['min_date', 'max_date'],
            'indexes' => 'date',
        ],
        'time' => [
            'label' => 'Time',
            'group' => 'time',
            'settings' => [],
            'indexes' => 'text',
        ],
        'datetime' => [
            'label' => 'Date and time',
            'group' => 'time',
            'settings' => ['min_date', 'max_date'],
            'indexes' => 'date',
        ],
        'select' => [
            'label' => 'Dropdown',
            'group' => 'choice',
            'settings' => [],
            'options' => true,
            'indexes' => 'text',
        ],
        'radio' => [
            'label' => 'One of several',
            'group' => 'choice',
            'settings' => [],
            'options' => true,
            'indexes' => 'text',
        ],
        'checkboxes' => [
            'label' => 'Several of',
            'group' => 'choice',
            'settings' => ['min_selected', 'max_selected'],
            'options' => true,
            'indexes' => 'text',
            'multiple' => true,
        ],
        'boolean' => [
            'label' => 'Yes or no',
            'group' => 'choice',
            'settings' => [],
            'indexes' => 'text',
        ],
    ];

    /** The registry as the builder consumes it. */
    public static function catalogue(): array
    {
        return collect(self::TYPES)
            ->map(fn (array $meta, string $id) => [
                'id' => $id,
                'label' => $meta['label'],
                'group' => $meta['group'],
                'settings' => $meta['settings'],
                'options' => (bool) ($meta['options'] ?? false),
                'multiple' => (bool) ($meta['multiple'] ?? false),
                'hint' => $meta['hint'] ?? null,
            ])
            ->values()
            ->all();
    }

    public static function exists(string $type): bool
    {
        return array_key_exists($type, self::TYPES);
    }

    /**
     * A label becomes a column name once, and then never changes.
     *
     * Lower-cased before the separators are collapsed, not after: `Str::snake`
     * splits on capitals, so "National ID" would come out `national_i_d` and
     * every acronym in a label would read as gibberish in the export header.
     */
    public static function keyFrom(string $label, array $taken = []): string
    {
        $base = Str::of($label)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->toString();
        // An Arabic-only label transliterates to nothing, and a key may not
        // start with a digit, so both fall back to a generic stem that the
        // uniqueness loop below then numbers.
        if ($base === '') {
            $base = 'field';
        } elseif (is_numeric($base[0])) {
            $base = 'field_' . $base;
        }

        $base = rtrim(Str::limit($base, 56, ''), '_');

        $key = $base;
        $suffix = 2;
        while (in_array($key, $taken, true)) {
            $key = $base . '_' . $suffix++;
        }

        return $key;
    }

    /**
     * Normalise before validating.
     *
     * An Arabic keyboard produces ٠١٢ and a copied id arrives with spaces or
     * a non-breaking space in it. Judging that raw would reject answers that
     * are perfectly correct, so the input is tidied first - and the tidy
     * version is what gets stored, which is also what makes a "unique" field
     * behave the way a person expects.
     */
    public static function normalise(string $type, mixed $value): mixed
    {
        if (is_array($value)) {
            return array_values(array_map(fn ($item) => self::normalise($type, $item), $value));
        }

        if ($value === null) {
            return null;
        }

        $value = trim(str_replace(["\u{00A0}", "\u{200F}", "\u{200E}"], ' ', (string) $value));

        if (in_array($type, ['number', 'digits', 'phone'], true)) {
            $value = self::toLatinDigits($value);
        }

        if (in_array($type, ['digits'], true)) {
            $value = preg_replace('/\D+/', '', $value);
        }

        if ($type === 'email') {
            $value = mb_strtolower($value);
        }

        return $value === '' ? null : $value;
    }

    /** ٠١٢٣ and ۰۱۲۳ are the same digits as 0123. */
    public static function toLatinDigits(string $value): string
    {
        $map = [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ];

        return strtr($value, $map);
    }

    /**
     * Laravel rules for one field.
     *
     * Uniqueness is deliberately absent: it is enforced by a unique index on
     * the answers table, because a validation lookup cannot stop two requests
     * that arrive in the same millisecond.
     */
    public static function rulesFor(array $field): array
    {
        $type = $field['type'] ?? 'text';
        $rules = $field['rules'] ?? [];
        $options = $field['options'] ?? [];

        $out = [($field['required'] ?? false) ? 'required' : 'nullable'];

        $min = $rules['min_length'] ?? null;
        $max = $rules['max_length'] ?? null;

        switch ($type) {
            case 'text':
                $out[] = 'string';
                $out[] = 'max:' . (int) ($max ?: 255);
                if ($min) $out[] = 'min:' . (int) $min;
                if (! empty($rules['pattern'])) $out[] = 'regex:' . self::safePattern($rules['pattern']);
                break;

            case 'letters':
                $out[] = 'string';
                $out[] = 'max:' . (int) ($max ?: 120);
                if ($min) $out[] = 'min:' . (int) $min;
                // Arabic and Latin letters, spaces, apostrophes and hyphens -
                // which is what a name actually looks like in both scripts.
                $out[] = 'regex:/^[\p{Arabic}\p{Latin}\s\'\x{2019}\-\.]+$/u';
                break;

            case 'textarea':
                $out[] = 'string';
                $out[] = 'max:' . (int) ($max ?: 5000);
                if ($min) $out[] = 'min:' . (int) $min;
                break;

            case 'email':
                $out[] = 'email:rfc';
                $out[] = 'max:190';
                break;

            case 'phone':
                $out[] = 'string';
                $out[] = 'regex:/^[+0-9][0-9\s()\-]{' . (int) (($min ?: 6) - 1) . ',' . (int) (($max ?: 20) - 1) . '}$/';
                break;

            case 'url':
                $out[] = 'url';
                $out[] = 'max:2048';
                break;

            case 'number':
                $out[] = ! empty($rules['integer']) ? 'integer' : 'numeric';
                if (isset($rules['min']) && $rules['min'] !== null && $rules['min'] !== '') $out[] = 'min:' . $rules['min'];
                if (isset($rules['max']) && $rules['max'] !== null && $rules['max'] !== '') $out[] = 'max:' . $rules['max'];
                break;

            case 'digits':
                $length = (int) ($rules['length'] ?? 0);
                $out[] = $length > 0 ? 'digits:' . $length : 'regex:/^\d+$/';
                break;

            case 'date':
            case 'datetime':
                $out[] = 'date';
                if (! empty($rules['min_date'])) $out[] = 'after_or_equal:' . $rules['min_date'];
                if (! empty($rules['max_date'])) $out[] = 'before_or_equal:' . $rules['max_date'];
                break;

            case 'time':
                $out[] = 'date_format:H:i';
                break;

            case 'select':
            case 'radio':
                $out[] = 'string';
                if ($options) $out[] = 'in:' . implode(',', array_map(fn ($o) => is_array($o) ? ($o['value'] ?? '') : $o, $options));
                break;

            case 'checkboxes':
                $out[] = 'array';
                if (! empty($rules['min_selected'])) $out[] = 'min:' . (int) $rules['min_selected'];
                if (! empty($rules['max_selected'])) $out[] = 'max:' . (int) $rules['max_selected'];
                break;

            case 'boolean':
                $out[] = 'boolean';
                break;
        }

        return $out;
    }

    /** Rules for the members of a multi-value field, or null when it has none. */
    public static function itemRulesFor(array $field): ?array
    {
        if (($field['type'] ?? '') !== 'checkboxes') {
            return null;
        }

        $options = $field['options'] ?? [];
        $values = array_map(fn ($o) => is_array($o) ? ($o['value'] ?? '') : $o, $options);

        return $values ? ['string', 'in:' . implode(',', $values)] : ['string'];
    }

    /**
     * A regex typed by a form owner runs on every submission, so it is wrapped
     * rather than trusted: no delimiters of their choosing, no flags, and a
     * length cap - the ingredients of a pattern that hangs a worker.
     */
    private static function safePattern(string $pattern): string
    {
        $clean = trim($pattern);
        $clean = preg_replace('/^[\/#~](.*)[\/#~][a-z]*$/s', '$1', $clean) ?? $clean;
        $clean = mb_substr($clean, 0, 200);

        return '/^' . str_replace('/', '\/', trim($clean, '^$')) . '$/u';
    }

    /**
     * Which indexed column an answer belongs in, and the comparison key used
     * for uniqueness.
     */
    public static function indexValue(string $type, mixed $value): array
    {
        $flat = is_array($value) ? implode(', ', $value) : (string) $value;
        $kind = self::TYPES[$type]['indexes'] ?? 'text';

        $row = [
            'value_text' => mb_substr($flat, 0, 2000),
            'value_number' => null,
            'value_date' => null,
            'value_hash' => null,
        ];

        if ($kind === 'number' && is_numeric($flat)) {
            $row['value_number'] = (float) $flat;
        }

        if ($kind === 'date') {
            try {
                $row['value_date'] = Carbon::parse($flat)->toDateTimeString();
            } catch (\Throwable) {
                $row['value_date'] = null;
            }
        }

        return $row;
    }

    /** The key a unique field is compared on: trimmed, folded, hashed. */
    public static function uniquenessHash(mixed $value): string
    {
        $flat = is_array($value) ? implode('|', $value) : (string) $value;

        return hash('sha256', mb_strtolower(preg_replace('/\s+/u', ' ', trim($flat))));
    }
}
