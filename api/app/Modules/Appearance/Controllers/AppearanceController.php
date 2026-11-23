<?php

namespace App\Modules\Appearance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Appearance\Models\SiteSetting;
use App\Modules\Appearance\Requests\UpdateThemeRequest;
use App\Modules\Appearance\Support\AppearanceDefaults;
use Illuminate\Http\JsonResponse;

class AppearanceController extends Controller
{
    public function show(): JsonResponse
    {
        $version = SiteSetting::whereIn('key', ['theme', 'home_sections'])->max('updated_at');

        return response()->json([
            'theme' => $this->theme(),
            'home_sections' => SiteSetting::get('home_sections') ?? AppearanceDefaults::homeSections(),
            'version' => $version ? \Illuminate\Support\Carbon::parse($version)->timestamp : 0,
        ])->header('Cache-Control', 'public, max-age=60');
    }

    public function updateTheme(UpdateThemeRequest $request): JsonResponse
    {
        $theme = array_replace_recursive($this->theme(), $request->validated());
        SiteSetting::put('theme', $theme);

        return response()->json($theme);
    }

    private function theme(): array
    {
        return array_replace_recursive(AppearanceDefaults::theme(), SiteSetting::get('theme') ?? []);
    }
}
