<?php

namespace App\Modules\Component\Repositories\Eloquent;

use App\Modules\Component\Models\Component;
use App\Modules\Component\Models\ComponentFeature;
use App\Modules\Component\Repositories\Interfaces\ComponentRepositoryInterface;
use App\Modules\Component\Support\ComponentStorage;
use App\Traits\PaginatesCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use RuntimeException;

class ComponentRepository implements ComponentRepositoryInterface
{
    use PaginatesCollection;
    use ComponentStorage;

    /** Always loaded: a component is never useful without these. */
    private const EAGER = ['features', 'categories'];

    /** Extensions that may be uploaded, mapped to their stored file_type. */
    private const ALLOWED = [
        'html' => 'html',
        'htm' => 'html',
        'jsx' => 'jsx',
        'tsx' => 'jsx',
        'vue' => 'vue',
    ];

    /** A single-file template that exceeds this is almost certainly not one. */
    private const MAX_BYTES = 2 * 1024 * 1024;

    protected Component $model;

    public function __construct(Component $component)
    {
        $this->model = $component;
    }

    public function all($search = null, $rowsPerPage = 10, $page = 1, array $filters = [])
    {
        $query = $this->model->with(self::EAGER);

        $this->applyFilters($query, $filters);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('name_ar', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('tagline', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('ordering')->orderByDesc('id')
            ->paginate($rowsPerPage, ['*'], 'page', $page);
    }

    public function published(array $filters = [])
    {
        $query = $this->model->with(self::EAGER)->published();

        $this->applyFilters($query, $filters);

        return $query->orderBy('ordering')->orderByDesc('id')->get();
    }

    public function find(int $id)
    {
        return $this->model->with(self::EAGER)->findOrFail($id);
    }

    public function findBySlug(string $slug, bool $publishedOnly = true)
    {
        $query = $this->model->with(self::EAGER)->where('slug', $slug);

        if ($publishedOnly) {
            $query->published();
        }

        return $query->firstOrFail();
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $features = $this->pullFeatures($data);
            $categories = $this->pullCategories($data);

            $component = $this->model->create($this->columns($data));

            $this->syncFeatures($component, $features);
            if ($categories !== null) {
                $component->categories()->sync($categories);
            }

            return $component->load(self::EAGER);
        });
    }

    public function update(int $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $component = $this->find($id);

            $features = $this->pullFeatures($data);
            $categories = $this->pullCategories($data);
            $previousSlug = $component->slug;

            $component->update($this->columns($data, $component));

            // A renamed slug means a renamed folder: move the file and rewrite
            // the stored path so the preview does not 404 after an edit.
            if (isset($data['slug']) && $data['slug'] !== $previousSlug) {
                $this->relocateFile($component, $previousSlug);
            }

            if ($features !== null) {
                $this->syncFeatures($component, $features);
            }

            if ($categories !== null) {
                $component->categories()->sync($categories);
            }

            return $component->fresh(self::EAGER);
        });
    }

    public function delete($id)
    {
        return DB::transaction(function () use ($id) {
            // Soft delete keeps the row recoverable, so the uploaded file is
            // deliberately left on disk to stay consistent with that.
            return $this->find((int) $id)->delete();
        });
    }

    public function toggleStatus(int $id)
    {
        $component = $this->find($id);
        $component->status = $component->status === 'published' ? 'draft' : 'published';
        $component->save();

        return $component->fresh(self::EAGER);
    }

    /**
     * Stores the uploaded template.
     *
     * The source is also cached on the row so the public endpoint can serve
     * the "view code" tab without touching the filesystem on every request.
     */
    public function storeFile(int $id, $file)
    {
        if (!$file) {
            throw new InvalidArgumentException('No file was uploaded.');
        }

        $component = $this->find($id);

        $extension = strtolower((string) $file->getClientOriginalExtension());

        if (!isset(self::ALLOWED[$extension])) {
            throw new InvalidArgumentException(
                'Unsupported file type. Upload a .html, .jsx, .tsx or .vue file.'
            );
        }

        if ($file->getSize() > self::MAX_BYTES) {
            throw new InvalidArgumentException('The file is larger than 2 MB.');
        }

        $source = file_get_contents($file->getRealPath());

        if ($source === false) {
            throw new RuntimeException('The uploaded file could not be read.');
        }

        // Reject anything that is not valid UTF-8 rather than storing bytes
        // that would corrupt the JSON response and the code view.
        if (!mb_check_encoding($source, 'UTF-8')) {
            throw new InvalidArgumentException('The file must be UTF-8 encoded text.');
        }

        $directory = $this->componentDirectory($component->slug);
        $absolute = public_path($directory);

        File::ensureDirectoryExists($absolute, 0755, true);

        // One file per component: clear the previous upload so a renamed
        // extension cannot leave an orphan behind.
        $this->purgeDirectory($absolute);

        $filename = 'template.' . $extension;
        $file->move($absolute, $filename);

        $component->update([
            'file_path' => $directory . '/' . $filename,
            'file_name' => $file->getClientOriginalName() ?: $filename,
            'file_size' => strlen($source),
            'file_type' => self::ALLOWED[$extension],
            'source' => $source,
        ]);

        return $component->fresh(self::EAGER);
    }

    public function removeFile(int $id)
    {
        $component = $this->find($id);

        if ($component->file_path) {
            $absolute = public_path($component->file_path);
            if (File::exists($absolute)) {
                File::delete($absolute);
            }
        }

        $component->update([
            'file_path' => null,
            'file_name' => null,
            'file_size' => null,
            'source' => null,
        ]);

        return $component->fresh(self::EAGER);
    }

    public function registerDownload(int $id)
    {
        // Incremented in the database rather than read-modify-write, so two
        // simultaneous downloads cannot lose a count.
        $this->model->whereKey($id)->increment('downloads');

        return $this->find($id);
    }

    /* ------------------------------------------------------------------ */

    /** Category and tag filters shared by the admin list and the public one. */
    private function applyFilters($query, array $filters): void
    {
        $categories = array_filter((array) ($filters['categories'] ?? []));

        if ($categories) {
            // Matching ANY of the selected categories, which is what a filter
            // chip row is normally taken to mean.
            $query->whereHas('categories', function ($q) use ($categories) {
                $q->whereIn('component_categories.slug', $categories);
            });
        }

        if (!empty($filters['type'])) {
            $query->where('file_type', $filters['type']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['tag'])) {
            $query->whereJsonContains('tags', $filters['tag']);
        }
    }

    /** Only real columns reach the model; features/categories are separate. */
    private function columns(array $data, ?Component $existing = null): array
    {
        $allowed = [
            'slug', 'name', 'name_ar', 'tagline', 'summary', 'file_type',
            'status', 'version', 'accent', 'tags', 'stack', 'preview_theme',
            'preview_height', 'user_id', 'ordering',
        ];

        $columns = array_intersect_key($data, array_flip($allowed));

        // file_type follows the uploaded file; it is never set by hand.
        unset($columns['file_type']);

        if ($existing === null) {
            $columns['status'] = $columns['status'] ?? 'draft';
        }

        return $columns;
    }

    private function pullFeatures(array &$data): ?array
    {
        if (!array_key_exists('features', $data)) {
            return null;
        }

        $features = $data['features'];
        unset($data['features']);

        return is_array($features) ? $features : [];
    }

    private function pullCategories(array &$data): ?array
    {
        if (!array_key_exists('categories', $data)) {
            return null;
        }

        $categories = $data['categories'];
        unset($data['categories']);

        return array_values(array_unique(array_map('intval', (array) $categories)));
    }

    private function syncFeatures(Component $component, ?array $features): void
    {
        if ($features === null) {
            return;
        }

        $component->features()->delete();

        foreach (array_values($features) as $index => $feature) {
            $label = is_array($feature) ? ($feature['label'] ?? '') : $feature;
            $label = trim((string) $label);

            if ($label === '') {
                continue;
            }

            ComponentFeature::create([
                'component_id' => $component->id,
                'label' => $label,
                'ordering' => $index,
            ]);
        }
    }

    private function relocateFile(Component $component, string $previousSlug): void
    {
        if (!$component->file_path) {
            return;
        }

        $from = public_path($this->componentDirectory($previousSlug));
        $to = public_path($this->componentDirectory($component->slug));

        if (File::isDirectory($from)) {
            File::ensureDirectoryExists(dirname($to), 0755, true);
            File::moveDirectory($from, $to, true);
        }

        $component->update([
            'file_path' => str_replace(
                self::ROOT . '/' . $previousSlug . '/',
                self::ROOT . '/' . $component->slug . '/',
                $component->file_path,
            ),
        ]);
    }

    private function purgeDirectory(string $absolute): void
    {
        foreach (File::glob($absolute . '/template.*') as $existing) {
            File::delete($existing);
        }
    }
}
