<?php

namespace App\Modules\Appearance\Support;

class AppearanceDefaults
{
    public const FONTS = [
        'IBM Plex Sans Arabic', 'Readex Pro', 'Noto Kufi Arabic', 'Tajawal', 'Cairo', 'Noto Naskh Arabic',
    ];

    public const SECTION_TYPES = [
        'hero_paths', 'stats_bar', 'continue_learning', 'feature_cards', 'image_text', 'cta_banner',
        'testimonials', 'faq', 'forms_showcase', 'learn_showcase', 'icons_showcase', 'draw_simulate',
        'image_tools', 'dev_tools', 'components_showcase', 'apps_showcase', 'programs_showcase',
    ];

    public const LANGUAGES = ['en', 'ar', 'hi', 'zh', 'tr'];

    /** http(s) or a single-slash relative path (no `//` or `/\`); D stops a trailing-newline bypass. */
    public const LINK_REGEX = '#^(https?://|/(?![/\x5c]))[^\x00-\x20\x7f]*$#D';

    public const HEX_REGEX = '/^#[0-9a-fA-F]{6}$/D';

    public static function theme(): array
    {
        return [
            'logo_url' => null,
            'logo_dark_url' => null,
            'favicon_url' => null,
            'font_body' => 'IBM Plex Sans Arabic',
            'font_heading' => 'IBM Plex Sans Arabic',
            'dark' => [
                'canvas' => '#0a0f1c', 'surface' => '#111827', 'text' => '#e8e6e1',
                'muted' => '#9aa3b5', 'accent' => '#c9a96e', 'hairline' => '#1c2438',
            ],
            'light' => [
                'canvas' => '#f7f5f0', 'surface' => '#ffffff', 'text' => '#1a1a1a',
                'muted' => '#6b6b6b', 'accent' => '#9a7a3f', 'hairline' => '#e7e2d6',
            ],
            'radius' => 14,
            'default_mode' => 'dark',
        ];
    }

    /** The frontend owns the default section list; empty means "use it". */
    public static function homeSections(): array
    {
        return [];
    }
}
