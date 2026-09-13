<?php

namespace App\Modules\Locale\Repositories\Eloquent;

use App\Modules\Locale\Models\Language;
use App\Modules\Locale\Repositories\Interfaces\LanguageRepositoryInterface;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class LanguageRepository implements LanguageRepositoryInterface
{
    protected $model;

    /**
     * Cache keys. getAllLanguages() and getActiveLanguages() return different
     * shapes (id+name vs the full row, all rows vs published only), so they
     * must never share a key: whichever ran first used to poison the other.
     */
    /** A month is fine: every write path flushes these keys explicitly. */
    protected const CACHE_TTL = 86400 * 30;

    protected const CACHE_ALL = 'languages.all';
    protected const CACHE_ACTIVE = 'languages.active';

    /** Every cache entry this repository owns, flushed together on writes. */
    protected const CACHE_KEYS = [self::CACHE_ALL, self::CACHE_ACTIVE];

    public function __construct(Language $language)
    {
        $this->model = $language;
    }

    protected function flushCache(): void
    {
        foreach (self::CACHE_KEYS as $key) {
            Cache::forget($key);
        }
    }

    public function getAllLanguages()
    {
        return Cache::remember(self::CACHE_ALL, self::CACHE_TTL, function () {
            return $this->model
                ->select('id', 'name')
                ->orderBy('name')
                ->get();
        });
    }

    public function getActiveLanguages()
    {
        return Cache::remember(self::CACHE_ACTIVE, self::CACHE_TTL, function () {
            return $this->model::where("status", 1)->get();
        });
    }

    public function createLanguage(array $data)
    {
        $this->flushCache();
        $language = $this->model::create($data);
        $this->createLanguageFiles($language->slug);
        return $language;
    }

    public function addWordToAdminFile($slug, Request $request)
    {
        try {
            $key = $request->input('key');
            $translation = $request->input('value');

            $adminFilePath = resource_path("lang/{$slug}/admin.php");

            if (!File::exists(dirname($adminFilePath))) {
                File::makeDirectory(dirname($adminFilePath), 0755, true);
            }

            $adminData = [];

            if (File::exists($adminFilePath)) {
                $adminData = include $adminFilePath;
                if (!is_array($adminData)) {
                    $adminData = [];
                }
            }

            $adminData[$key] = $translation;
            $phpCode = "<?php\n\nreturn " . var_export($adminData, true) . ";\n";
            File::put($adminFilePath, $phpCode);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getLanguageBySlug($slug)
    {
        $languageDir = resource_path("lang/{$slug}");

        if (!File::exists($languageDir)) {
            return null;
        }

        $combinedData = [];
        $adminFilePath = "{$languageDir}/admin.php";
        if (File::exists($adminFilePath)) {
            $adminData = include $adminFilePath;
            if (is_array($adminData)) {
                foreach ($adminData as $key => $value) {
                    $combinedData[] = ['key' => $key, 'value' => $value];
                }
            }
        }

        return $combinedData;
    }

    public function updateLanguageStatus($id)
    {
        $this->flushCache();
        $language = $this->model::findOrFail($id);
        $language->update([
            'status' => $language->status == 1 ? 0 : 1,
        ]);

        return $language;
    }

    public function deleteLanguage($id)
    {
        $this->flushCache();
        $language = $this->model::findOrFail($id);
        $language->delete();

        $languageDir = resource_path("lang/{$language->slug}");
        if (File::exists($languageDir)) {
            File::deleteDirectory($languageDir);
        }

        return true;
    }

    public function deleteLanguages(array $ids)
    {
        $this->flushCache();
        $languages = $this->model::whereIn('id', $ids)->get();
        foreach ($languages as $language) {
            $slug = $language->slug;

            $language->delete();

            $languageDir = resource_path("lang/{$slug}");
            if (File::exists($languageDir)) {
                File::deleteDirectory($languageDir);
            }
        }

        return true;
    }

    protected function createLanguageFiles($slug)
    {
        $languageDir = resource_path("lang/{$slug}");

        if (!File::exists($languageDir)) {
            File::makeDirectory($languageDir, 0755, true);
        }

        $adminContent = "<?php\n\nreturn [\n    // مصفوفة للإدارة\n];\n";
        File::put("{$languageDir}/admin.php", $adminContent);
    }
}
