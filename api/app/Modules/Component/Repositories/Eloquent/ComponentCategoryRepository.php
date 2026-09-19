<?php

namespace App\Modules\Component\Repositories\Eloquent;

use App\Modules\Component\Models\ComponentCategory;
use App\Modules\Component\Repositories\Interfaces\ComponentCategoryRepositoryInterface;
use Illuminate\Support\Facades\DB;

class ComponentCategoryRepository implements ComponentCategoryRepositoryInterface
{
    protected ComponentCategory $model;

    public function __construct(ComponentCategory $category)
    {
        $this->model = $category;
    }

    public function all($search = null, $rowsPerPage = 10, $page = 1)
    {
        $query = $this->model->withCount('components');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('name_ar', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('ordering')->orderBy('name')
            ->paginate($rowsPerPage, ['*'], 'page', $page);
    }

    /**
     * Categories for the public filter bar.
     *
     * The count is restricted to published components, otherwise a chip could
     * advertise results a visitor is not allowed to see.
     */
    public function active()
    {
        return $this->model
            ->active()
            ->withCount(['components' => fn($q) => $q->where('status', 'published')])
            ->orderBy('ordering')
            ->orderBy('name')
            ->get();
    }

    public function find(int $id)
    {
        return $this->model->withCount('components')->findOrFail($id);
    }

    public function create(array $data)
    {
        return DB::transaction(fn() => $this->model->create($data)->fresh());
    }

    public function update(int $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $category = $this->model->findOrFail($id);
            $category->update($data);

            return $category->fresh();
        });
    }

    public function delete($id)
    {
        return DB::transaction(function () use ($id) {
            $category = $this->model->findOrFail((int) $id);

            // The pivot rows go with it, so a component simply loses this
            // category rather than keeping a dangling reference.
            $category->components()->detach();

            return $category->delete();
        });
    }
}
