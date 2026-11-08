<?php

namespace App\Modules\Learning\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * The vocabulary of a lesson body.
 *
 * A lesson is an ordered list of blocks. Each block has a `type` and the
 * fields that type needs; anything else is dropped on save so the stored JSON
 * stays exactly what the renderer understands. The dashboard's block editor
 * and the public renderer (sydev-front/src/page/site/learn/blocks) implement
 * the same list - a new type is added in all three places.
 */
class LessonBlocks
{
    public const TYPES = [
        'text', 'callout', 'code', 'terminal', 'image', 'video',
        'animation', 'quiz', 'keyterms', 'divider',
    ];

    public const CALLOUTS = ['note', 'tip', 'info', 'warning', 'danger', 'lab'];

    public const ANIMATIONS = ['sequence', 'flow', 'layers', 'cycle'];

    /** Fields kept for each type, beyond `type` and `id`. */
    private const FIELDS = [
        'text' => ['markdown'],
        'callout' => ['variant', 'title', 'markdown'],
        'code' => ['language', 'filename', 'code', 'caption', 'highlight'],
        'terminal' => ['title', 'prompt', 'lines', 'caption'],
        'image' => ['url', 'media_id', 'alt', 'caption', 'width'],
        'video' => ['url', 'media_id', 'caption', 'poster'],
        'animation' => ['kind', 'title', 'caption', 'data', 'autoplay', 'speed'],
        'quiz' => ['title', 'questions'],
        'keyterms' => ['title', 'items'],
        'divider' => [],
    ];

    /**
     * Validate and normalise a lesson body.
     *
     * @throws ValidationException
     */
    public static function sanitize(?array $blocks, string $attribute = 'blocks'): array
    {
        $blocks = array_values($blocks ?? []);
        $rules = [];

        // Validated under a plain "blocks" key: the caller's attribute name
        // may contain dots, which the validator would read as nesting.
        foreach ($blocks as $index => $block) {
            $prefix = "blocks.{$index}";
            $rules["{$prefix}.type"] = ['required', 'string', Rule::in(self::TYPES)];
            $type = is_array($block) ? ($block['type'] ?? null) : null;

            foreach (self::rulesFor($type) as $field => $fieldRules) {
                $rules["{$prefix}.{$field}"] = $fieldRules;
            }
        }

        $validator = Validator::make(['blocks' => $blocks], $rules);
        $validator->after(function ($validator) use ($blocks): void {
            foreach ($blocks as $index => $block) {
                if (($block['type'] ?? null) === 'image' && empty($block['url']) && empty($block['media_id'])) {
                    $validator->errors()->add("blocks.{$index}.url", 'An image block needs an uploaded image or an image URL.');
                }
            }
        });

        if ($validator->fails()) {
            $messages = [];
            foreach ($validator->errors()->toArray() as $key => $list) {
                $messages[$attribute.substr($key, strlen('blocks'))] = $list;
            }
            throw ValidationException::withMessages($messages);
        }

        return array_map(function (array $block, int $index): array {
            $clean = [
                'id' => is_string($block['id'] ?? null) && $block['id'] !== ''
                    ? substr($block['id'], 0, 40)
                    : 'b'.($index + 1).'-'.substr(md5(json_encode($block)), 0, 6),
                'type' => $block['type'],
            ];
            foreach (self::FIELDS[$block['type']] as $field) {
                if (array_key_exists($field, $block) && $block[$field] !== null && $block[$field] !== '') {
                    $clean[$field] = $block[$field];
                }
            }

            return $clean;
        }, $blocks, array_keys($blocks));
    }

    private static function rulesFor(?string $type): array
    {
        $url = ['nullable', 'string', 'max:2048', 'regex:/^(https?:\/\/|\/)/i'];

        return match ($type) {
            'text' => ['markdown' => ['required', 'string', 'max:200000']],
            'callout' => [
                'variant' => ['required', Rule::in(self::CALLOUTS)],
                'title' => ['nullable', 'string', 'max:255'],
                'markdown' => ['required', 'string', 'max:50000'],
            ],
            'code' => [
                'language' => ['required', 'string', 'max:40'],
                'filename' => ['nullable', 'string', 'max:255'],
                'code' => ['required', 'string', 'max:100000'],
                'caption' => ['nullable', 'string', 'max:2000'],
                'highlight' => ['nullable', 'array'],
                'highlight.*' => ['integer', 'min:1'],
            ],
            'terminal' => [
                'title' => ['nullable', 'string', 'max:255'],
                'prompt' => ['nullable', 'string', 'max:80'],
                'lines' => ['required', 'array', 'min:1', 'max:200'],
                'lines.*.cmd' => ['nullable', 'string', 'max:2000'],
                'lines.*.output' => ['nullable', 'string', 'max:20000'],
                'lines.*.prompt' => ['nullable', 'string', 'max:80'],
                'lines.*.comment' => ['nullable', 'string', 'max:500'],
                'caption' => ['nullable', 'string', 'max:2000'],
            ],
            'image' => [
                // Either an external/absolute url or an uploaded media id;
                // checked together in sanitize() since the pair is nested.
                'url' => $url,
                'media_id' => ['nullable', 'integer'],
                'alt' => ['required', 'string', 'max:500'],
                'caption' => ['nullable', 'string', 'max:2000'],
                'width' => ['nullable', Rule::in(['narrow', 'normal', 'wide'])],
            ],
            'video' => [
                'url' => array_merge(['required'], array_slice($url, 1)),
                'media_id' => ['nullable', 'integer'],
                'caption' => ['nullable', 'string', 'max:2000'],
                'poster' => $url,
            ],
            'animation' => [
                'kind' => ['required', Rule::in(self::ANIMATIONS)],
                'title' => ['nullable', 'string', 'max:255'],
                'caption' => ['nullable', 'string', 'max:2000'],
                'data' => ['required', 'array'],
                'data.steps' => ['nullable', 'array', 'max:60'],
                'autoplay' => ['nullable', 'boolean'],
                'speed' => ['nullable', 'numeric', 'min:0.25', 'max:4'],
            ],
            'quiz' => [
                'title' => ['nullable', 'string', 'max:255'],
                'questions' => ['required', 'array', 'min:1', 'max:30'],
                'questions.*.question' => ['required', 'string', 'max:2000'],
                'questions.*.options' => ['required', 'array', 'min:2', 'max:8'],
                'questions.*.options.*' => ['required', 'string', 'max:1000'],
                'questions.*.answer' => ['required'],
                'questions.*.explanation' => ['nullable', 'string', 'max:4000'],
            ],
            'keyterms' => [
                'title' => ['nullable', 'string', 'max:255'],
                'items' => ['required', 'array', 'min:1', 'max:60'],
                'items.*.term' => ['required', 'string', 'max:255'],
                'items.*.definition' => ['required', 'string', 'max:4000'],
            ],
            default => [],
        };
    }
}
