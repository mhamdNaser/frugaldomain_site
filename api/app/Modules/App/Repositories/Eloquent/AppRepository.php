<?php

namespace App\Modules\App\Repositories\Eloquent;

use App\Modules\App\Models\App;
use App\Modules\App\Models\AppFeature;
use App\Modules\App\Models\AppImage;
use App\Modules\App\Repositories\Interfaces\AppRepositoryInterface;
use App\Modules\App\Support\AppStorage;
use App\Traits\ManageFiles;
use App\Traits\PaginatesCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class AppRepository implements AppRepositoryInterface
{
    use ManageFiles;
    use PaginatesCollection;
    use AppStorage;

    /** Relations every read needs — the resource reads all three. */
    protected const EAGER = ['features', 'images'];

    protected $model;

    public function __construct(App $app)
    {
        $this->model = $app;
    }

    public function all($search = null, $rowsPerPage = 10, $page = 1)
    {
        $query = App::with(self::EAGER);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('tagline', 'like', "%{$search}%");
            });
        }

        $items = $query->orderBy('ordering')->orderBy('id', 'desc')->get();

        return $this->paginate($items, $rowsPerPage, $page);
    }

    public function published()
    {
        return App::with(self::EAGER)
            ->published()
            ->orderBy('ordering')
            ->orderBy('id', 'desc')
            ->get();
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

            $app = $this->model->create($this->columns($data));

            $this->syncFeatures($app, $features);

            return $app->load(self::EAGER);
        });
    }

    public function update(int $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $app = $this->find($id);

            $features = $this->pullFeatures($data);
            $previousSlug = $app->slug;

            $app->update($this->columns($data, $app));

            // A renamed slug means a renamed asset folder: move the files and
            // rewrite the stored paths so nothing 404s after the edit.
            if (isset($data['slug']) && $data['slug'] !== $previousSlug) {
                $this->relocateAssets($app, $previousSlug);
            }

            if ($features !== null) {
                $this->syncFeatures($app, $features);
            }

            return $app->fresh(self::EAGER);
        });
    }

    public function delete($id)
    {
        return DB::transaction(function () use ($id) {
            $app = $this->find($id);

            // Soft delete keeps the row recoverable, so the uploaded files are
            // deliberately left on disk to stay consistent with that.
            return $app->delete();
        });
    }

    public function toggleStatus(int $id)
    {
        $app = $this->find($id);
        $app->status = $app->status === 'published' ? 'draft' : 'published';
        $app->save();

        return $app->fresh(self::EAGER);
    }

    /**
     * Splits the validated payload into scalar columns, leaving out the
     * relation payloads and any upload-managed column a client must not set
     * directly.
     */
    protected function columns(array $data, ?App $app = null): array
    {
        $columns = [
            'slug',
            'name',
            'tagline',
            'summary',
            'type',
            'status',
            'version',
            'updated_on',
            'accent',
            'tags',
            'stack',
            'docs',
            'repository',
            'preview_mode',
            'preview_url',
            'subdomain',
            'preview_embed',
            'preview_open_in_new_tab',
            'ordering',
        ];

        $payload = [];
        foreach ($columns as $column) {
            if (array_key_exists($column, $data)) {
                $payload[$column] = $data[$column];
            }
        }

        if ($app === null) {
            $payload['user_id'] = $data['user_id'] ?? auth()->id();
        }

        return $payload;
    }

    /**
     * Pulls the features list out of the payload. Returns null when the key is
     * absent so a partial update does not wipe the existing rows.
     *
     * @return array<int,string>|null
     */
    protected function pullFeatures(array &$data): ?array
    {
        if (!array_key_exists('features', $data)) {
            return null;
        }

        $features = $data['features'];
        unset($data['features']);

        if (!is_array($features)) {
            return [];
        }

        // Accept either ["text", ...] or [{"label": "text"}, ...].
        return array_values(array_filter(array_map(function ($feature) {
            $label = is_array($feature) ? ($feature['label'] ?? '') : $feature;

            return trim((string) $label);
        }, $features), fn($label) => $label !== ''));
    }

    /**
     * Replaces the whole feature list. Rewriting is cheaper and far simpler
     * than diffing, and the list is small by nature.
     *
     * @param array<int,string>|null $features
     */
    protected function syncFeatures(App $app, ?array $features): void
    {
        if ($features === null) {
            return;
        }

        AppFeature::where('app_id', $app->id)->delete();

        foreach ($features as $index => $label) {
            AppFeature::create([
                'app_id' => $app->id,
                'label' => $label,
                'ordering' => $index,
            ]);
        }
    }

    /**
     * Moves public/apps/<old>/ to public/apps/<new>/ after a slug change and
     * rewrites every stored path prefix accordingly.
     */
    protected function relocateAssets(App $app, string $previousSlug): void
    {
        $from = $this->appDirectory($previousSlug);
        $to = $this->appDirectory($app->slug);

        $fromFull = public_path($from);
        $toFull = public_path($to);

        if (File::isDirectory($fromFull) && !File::exists($toFull)) {
            File::moveDirectory($fromFull, $toFull);
        }

        $rewrite = fn(?string $path) => $path
            ? preg_replace('#^' . preg_quote($from, '#') . '/#', $to . '/', $path)
            : $path;

        $app->forceFill([
            'archive_path' => $rewrite($app->archive_path),
            'main_image' => $rewrite($app->main_image),
            'cover' => $rewrite($app->cover),
        ])->save();

        foreach (AppImage::where('app_id', $app->id)->get() as $image) {
            $image->path = $rewrite($image->path);
            $image->save();
        }
    }
}
