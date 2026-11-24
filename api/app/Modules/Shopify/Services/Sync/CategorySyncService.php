<?php

namespace App\Modules\Shopify\Services\Sync;

use App\Modules\Catalog\Models\Category;
use App\Modules\Stores\Models\Store;
use Illuminate\Support\Str;

class CategorySyncService
{
    /**
     * مزامنة بيانات الفيندور
     *
     * @return int|null معرف الفيندور بعد المزامنة
     */
    public function sync(Store $store, ?string $categoryId, ?string $categoryName): ?int
    {
        if (empty($categoryName)) {
            return null;
        }

        // categories are unique per store by name, so fall back to a same-name row
        // (e.g. created from the dashboard) instead of inserting a duplicate.
        $category = Category::query()->where('store_id', $store->id)->where('shopify_category_id', $categoryId)->first()
            ?? Category::query()->where('store_id', $store->id)->where('name', $categoryName)->first()
            ?? new Category(['store_id' => $store->id]);

        $category->fill([
            'shopify_category_id' => $categoryId,
            'name' => $categoryName,
            'slug' => Str::slug($categoryName ?? ''),
        ])->save();

        return $category->id;
    }
}
