<?php

namespace App\Modules\Icon\Repositories\Eloquent;

use App\Modules\Icon\Models\Icon;
use App\Modules\Icon\Models\IconFiles;
use App\Modules\Icon\Repositories\Interfaces\IconRepositoryInterface;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Encoders\PngEncoder;
use Illuminate\Support\Str;
use App\Traits\ManageFiles;
use App\Traits\PaginatesCollection;
use Illuminate\Support\Facades\Cache;

class IconRepository implements IconRepositoryInterface
{
    use ManageFiles;
    use PaginatesCollection;

    protected $model;

    public function __construct(Icon $icon)
    {
        $this->model = $icon;
    }

    /**
     * Cache key prefix. It carries a version number that is bumped whenever an
     * icon changes, which invalidates every derived key at once — the database
     * cache store has no tag support, and the filter keys are unbounded.
     */
    public const CACHE_VERSION_KEY = 'icons_cache_version';

    public static function cacheVersion(): int
    {
        return (int) Cache::get(self::CACHE_VERSION_KEY, 1);
    }

    public static function bumpCacheVersion(): void
    {
        Cache::forever(self::CACHE_VERSION_KEY, self::cacheVersion() + 1);
    }

    /**
     * NOTE: query results are intentionally NOT cached as Eloquent collections.
     * Serialising a few hundred hydrated models (with their relations) into the
     * database cache store blows past MySQL's max_allowed_packet and the write
     * fails with "Got a packet bigger than 'max_allowed_packet' bytes".
     * The controller caches the small, already-transformed resource arrays.
     */
    public function all($search = null, $rowsPerPage = 10, $page = 1)
    {
        // IconResource reads the SVG/PNG paths off the files relation, so it
        // has to be eager loaded or every icon costs an extra query.
        $query = Icon::with(['category', 'files']);

        // البحث في العنوان والوصف معاً على مستوى قاعدة البيانات
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $items = $query->orderBy('id', 'desc')->get();

        // استخدام التريت لتطبيق الباجنيشن
        return $this->paginate($items, $rowsPerPage, $page);
    }

    public function allWithoutPagination($search = null, $category = null)
    {
        $query = Icon::query()->with(['category', 'files']);

        // فلترة الكاتيجوري
        if ($category) {
            $query->whereHas('category', function ($q) use ($category) {
                $q->where('name', $category);
            });
        }

        // فلترة البحث على النصوص
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('tags', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('id', 'desc')->get();
    }

    public function find(int $id)
    {
        return $this->model->with('category', 'user')->findOrFail($id);
    }

    public function create(array $data)
    {
        $slug = Str::slug($data['title']);
        $storagePath = public_path('icons');
        if (!file_exists($storagePath)) {
            mkdir($storagePath, 0755, true);
        }

        // حفظ SVG كنص
        $svgPath = $this->uploadFile($data['icon_text'], 'icons', $slug, 'svg');

        // 🔸 حساب الحجم من الملف الفعلي
        $svgFullPath = public_path($svgPath);
        $svgSize = file_exists($svgFullPath) ? filesize($svgFullPath) : 0;

        // تحويل SVG إلى PNG
        $driver = extension_loaded('imagick') ? new ImagickDriver() : new GdDriver();
        $manager = new ImageManager($driver);
        $image = $manager->read($data['icon_text']);
        $encoded = $image->encode(new PngEncoder());
        $pngPath = $this->uploadFile($encoded, 'icons', $slug, 'png');

        // 🔸 حساب الحجم والأبعاد من الملف الفعلي
        $pngFullPath = public_path($pngPath);
        $pngSize = file_exists($pngFullPath) ? filesize($pngFullPath) : 0;
        $pngDimensions = null;
        if (file_exists($pngFullPath)) {
            $info = getimagesize($pngFullPath);
            $pngDimensions = $info ? "{$info[0]}x{$info[1]}" : null;
        }

        // إنشاء الأيقونة بعد توليد الملفين، لأن العمودين file_svg و file_png
        // معرّفان NOT NULL في المهاجرة ويجب حفظ مسارَي الملفين معهما.
        $icon = $this->model->create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'user_id' => $data['user_id'] ?? null,
            'is_premium' => $data['is_premium'] ?? false,
            'is_active' => $data['is_active'] ?? true,
            'tags' => $data['tags'] ?? [],
            'file_svg' => $svgPath,
            'file_png' => $pngPath,
        ]);

        // 🔸 SVG غالباً ما فيها width/height فعلي، نتركها null
        IconFiles::create([
            'icon_id' => $icon->id,
            'file_name' => $slug . '.svg',
            'file_path' => $svgPath,
            'file_type' => 'svg',
            'file_size' => $svgSize,
            'dimensions' => null,
        ]);

        IconFiles::create([
            'icon_id' => $icon->id,
            'file_name' => $slug . '.png',
            'file_path' => $pngPath,
            'file_type' => 'png',
            'file_size' => $pngSize,
            'dimensions' => $pngDimensions,
        ]);

        self::bumpCacheVersion();

        return $icon->load('files');
    }

    public function update(int $id, array $data)
    {
        self::bumpCacheVersion();
        $icon = $this->find($id);
        $icon->update($data);
        return $icon;
    }

    public function delete($id)
    {
        self::bumpCacheVersion();
        $icon = $this->find($id);
        return $icon->delete();
    }

    public function deleteArray(array $ids)
    {
        self::bumpCacheVersion();
        return $this->model->destroy($ids);
    }

    public function toggleStatus(int $id)
    {
        self::bumpCacheVersion();
        $icon = $this->find($id);
        $icon->is_active = !$icon->is_active;
        $icon->save();
        return $icon;
    }
}
