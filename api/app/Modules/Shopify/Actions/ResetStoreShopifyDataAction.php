<?php

namespace App\Modules\Shopify\Actions;

use App\Modules\Catalog\Models\Product;
use App\Modules\Shopify\Services\ShopifyClient;
use App\Modules\Stores\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Clears a store's copy of Shopify data before a full import, so the import rebuilds it
 * exactly as Shopify has it (no duplicate or stale images, links, orders' sub-records...).
 *
 * Only tables that the full import rebuilds are listed. Kept on purpose:
 * - products, variants, customers (+ addresses), orders (+ items), collections, locations:
 *   unique on their Shopify id so they cannot duplicate, and the mobile app's carts,
 *   devices and sessions hang off them;
 * - metafields / metaobjects, tax rates, inventory movements: not fully rebuilt by the import;
 * - everything local to this site (team, settings, logs, sync history, carts, analytics).
 */
class ResetStoreShopifyDataAction
{
    /** Children before parents; FK cascades take care of pivots without a store_id. */
    private const TABLES = [
        'files', 'product_media', 'collection_products',
        'inventory_levels', 'inventories',
        'price_list_items', 'price_lists', 'markets',
        'product_selling_plan_groups', 'selling_plan_subscriptions', 'selling_plans', 'selling_plan_groups',
        'discount_usages', 'discount_codes', 'discounts',
        'draft_order_items', 'draft_orders',
        'fulfillment_tracking', 'fulfillment_items', 'fulfillments',
        'fulfillment_order_items', 'fulfillment_orders', 'fulfillment_services',
        'order_return_items', 'exchanges', 'reverse_fulfillments', 'order_returns',
        'order_item_duties', 'order_duties', 'order_risks', 'order_channels',
        'refund_items', 'payment_transactions', 'refunds', 'tax_lines',
        'customer_marketing_consents', 'shipping_zones',
        'theme_assets', 'themes',
        'comments', 'articles', 'blogs', 'pages', 'menu_items', 'menus',
        'options', 'vendors', 'product_types', 'categories', // options cascade to option_values, product_options, variant_option_values
    ];

    /** Fails (and wipes nothing) when Shopify cannot be reached, so the store is never left empty. */
    public function ensureShopifyReachable(Store $store): void
    {
        try {
            (new ShopifyClient($store))->query('query { shop { id } }');
        } catch (Throwable $e) {
            Log::warning('Store reset skipped: Shopify unreachable', ['store_id' => $store->id, 'error' => $e->getMessage()]);
            abort(503, 'Shopify could not be reached, so nothing was cleared. Please try again.');
        }
    }

    /** @return array<string, int> rows deleted per table */
    public function execute(Store $store): array
    {
        $deleted = [];
        $cachedFiles = [];

        DB::transaction(function () use ($store, &$deleted, &$cachedFiles) {
            // Dashboard uploads keep a local copy on the public disk; Shopify rows point at the CDN.
            $cachedFiles = DB::table('files')->where('store_id', $store->id)->where('disk', 'public')
                ->where('path', 'not like', 'http%')->pluck('path')->all();

            // Only product tags are rebuilt by the import; customer and order tags stay.
            $deleted['taggables'] = DB::table('taggables')
                ->where('taggable_type', Product::class)
                ->whereIn('taggable_id', DB::table('products')->where('store_id', $store->id)->select('id'))
                ->delete();

            foreach (self::TABLES as $table) {
                $deleted[$table] = DB::table($table)->where('store_id', $store->id)->delete();
            }

            // The cover image is re-read from Shopify with the product.
            DB::table('products')->where('store_id', $store->id)->update(['featured_image' => null]);
        });

        Storage::disk('public')->delete($cachedFiles);
        Log::info('Store Shopify data cleared before full import', ['store_id' => $store->id, 'deleted' => array_filter($deleted)]);

        return $deleted;
    }
}
