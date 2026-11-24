<?php

namespace App\Modules\Appearance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Appearance\Models\SiteSetting;
use App\Modules\Appearance\Requests\UpdateHomeSectionsRequest;
use App\Modules\Appearance\Requests\UpdateThemeRequest;
use App\Modules\Appearance\Support\AppearanceDefaults;
use App\Modules\Appearance\Support\SectionSanitizer;
use Illuminate\Http\JsonResponse;

class AppearanceController extends Controller
{
    public function show(): JsonResponse
    {
        $version = SiteSetting::whereIn('key', ['theme', 'home_sections', 'promo'])->max('updated_at');

        return response()->json([
            'theme' => $this->theme(),
            'home_sections' => SiteSetting::get('home_sections') ?? AppearanceDefaults::homeSections(),
            'promo' => $this->promo(),
            'version' => $version ? \Illuminate\Support\Carbon::parse($version)->timestamp : 0,
        ])->header('Cache-Control', 'public, max-age=60');
    }

    public function updateTheme(UpdateThemeRequest $request): JsonResponse
    {
        $theme = array_replace_recursive($this->theme(), $request->validated());
        SiteSetting::put('theme', $theme);

        return response()->json($theme);
    }

    public function updateHomeSections(UpdateHomeSectionsRequest $request): JsonResponse
    {
        $sections = SectionSanitizer::clean($request->validated('sections'));
        SiteSetting::put('home_sections', $sections);

        return response()->json($sections);
    }

    public function updatePromo(\Illuminate\Http\Request $request): JsonResponse
    {
        $langs = ['en', 'ar', 'hi', 'zh', 'tr'];
        $data = $request->validate([
            'enabled' => ['sometimes', 'boolean'],
            'link' => ['sometimes', 'string', 'max:255', 'regex:'.AppearanceDefaults::LINK_REGEX],
            'i18n' => ['sometimes', 'array'],
            'i18n.*' => ['array'],
            'i18n.*.*' => ['nullable', 'string', 'max:300'],
        ]);

        $clean = collect($data['i18n'] ?? [])->only($langs)->map(fn ($t) => collect($t)
            ->only(['title', 'text', 'button'])
            ->map(fn ($v) => trim(strip_tags((string) $v)))
            ->filter(fn ($v) => $v !== '')
            ->all())->all();

        $promo = array_replace_recursive($this->promo(), array_filter([
            'enabled' => $data['enabled'] ?? null,
            'link' => $data['link'] ?? null,
        ], fn ($v) => $v !== null), ['i18n' => $clean]);
        SiteSetting::put('promo', $promo);

        return response()->json($promo);
    }

    private function promo(): array
    {
        return array_replace_recursive(AppearanceDefaults::promo(), SiteSetting::get('promo') ?? []);
    }

    private function theme(): array
    {
        return array_replace_recursive(AppearanceDefaults::theme(), SiteSetting::get('theme') ?? []);
    }
}
