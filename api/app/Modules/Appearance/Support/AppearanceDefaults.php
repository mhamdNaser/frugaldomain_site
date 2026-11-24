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
        'shopify_showcase',
    ];

    public const LANGUAGES = ['en', 'ar', 'hi', 'zh', 'tr'];

    /** http(s) or a single-slash relative path (no `//` or `/\`); D stops a trailing-newline bypass. */
    public const LINK_REGEX = '#^(https?://|/(?![/\x5c]))[^\x00-\x20\x7f]*$#D';

    // Theme urls are printed into CSS url(...): also no quotes, parentheses or backslashes (fg_sanitize in scripts/appearance-lib.php).
    public const THEME_URL_REGEX = '#^(https?://|/(?![/\x5c]))[^\x00-\x20\x7f"\'()\x5c]*$#D';

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
                'muted' => '#6b6b6b', 'accent' => '#80632c', 'hairline' => '#e7e2d6',
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

    /** Popup that points visitors to the Shopify feature page. */
    public static function promo(): array
    {
        return [
            'enabled' => true,
            'link' => '/shopify',
            'i18n' => [
                'en' => ['title' => 'New: run your Shopify store', 'text' => 'Products, orders and customers in one place, synced both ways. Free.', 'button' => 'See how it works'],
                'ar' => ['title' => 'جديد: أدِر متجر Shopify', 'text' => 'المنتجات والطلبات والعملاء في مكان واحد، بمزامنة بالاتجاهين. مجاناً.', 'button' => 'اكتشف الميزة'],
                'hi' => ['title' => 'नया: अपना Shopify स्टोर चलाएँ', 'text' => 'उत्पाद, ऑर्डर और ग्राहक एक ही जगह, दोनों तरफ़ सिंक। मुफ़्त।', 'button' => 'देखें यह कैसे काम करता है'],
                'zh' => ['title' => '新功能：管理您的 Shopify 店铺', 'text' => '商品、订单和客户集中在一处，双向同步。免费。', 'button' => '了解详情'],
                'tr' => ['title' => 'Yeni: Shopify mağazanızı yönetin', 'text' => 'Ürünler, siparişler ve müşteriler tek yerde, iki yönlü senkron. Ücretsiz.', 'button' => 'Nasıl çalıştığını görün'],
            ],
        ];
    }
}
