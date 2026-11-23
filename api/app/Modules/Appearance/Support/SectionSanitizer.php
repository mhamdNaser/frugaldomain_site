<?php

namespace App\Modules\Appearance\Support;

use Illuminate\Validation\ValidationException;

class SectionSanitizer
{
    /** Shape is already validated by the request; this cleans props/i18n values recursively. */
    public static function clean(array $sections): array
    {
        // Error keys carry the section index (sections.N.props...), like the request's own rules.
        $sections = array_values($sections);

        return array_map(fn ($s, $i) => [
            'id' => $s['id'], 'type' => $s['type'], 'visible' => (bool) $s['visible'],
            'props' => self::walk($s['props'], "sections.$i.props"), 'i18n' => self::walk($s['i18n'], "sections.$i.i18n"),
        ], $sections, array_keys($sections));
    }

    private static function walk(array $node, string $path): array
    {
        foreach ($node as $key => $value) {
            $at = "$path.$key";
            if (is_array($value)) {
                $node[$key] = self::walk($value, $at);
            } elseif (is_string($value) || $value === null) {
                $node[$key] = self::field((string) $key, (string) $value, $at);
            }
        }

        return $node;
    }

    private static function field(string $key, string $value, string $at): string
    {
        $k = strtolower($key);
        if (preg_match('/(url|link|href|image)$/', $k)) {
            return $value === '' || preg_match(AppearanceDefaults::LINK_REGEX, $value) ? $value : self::fail($at, 'Invalid link.');
        }
        if ($k === 'color') {
            return preg_match(AppearanceDefaults::HEX_REGEX, $value) ? $value : self::fail($at, 'Invalid colour.');
        }

        return mb_substr(trim(strip_tags($value)), 0, 2000);
    }

    private static function fail(string $at, string $msg): never
    {
        throw ValidationException::withMessages([$at => $msg]);
    }
}
