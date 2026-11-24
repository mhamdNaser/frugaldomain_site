<?php

namespace App\Modules\User\Team;

use App\Modules\Billing\Controllers\PaymentTransactionController;
use App\Modules\Billing\Controllers\RefundController;
use App\Modules\Billing\Controllers\RefundItemController;
use App\Modules\Catalog\Controllers\ProductController;
use App\Modules\Catalog\Controllers\ProductsDashboardController;
use App\Modules\Catalog\Controllers\ProductVariantController;
use App\Modules\Catalog\Controllers\References\CategoriesController;
use App\Modules\Catalog\Controllers\References\CollectionsController;
use App\Modules\Catalog\Controllers\References\OptionsController;
use App\Modules\Catalog\Controllers\References\ProductTypesController;
use App\Modules\Catalog\Controllers\References\TagsController;
use App\Modules\Catalog\Controllers\References\VendorsController;
use App\Modules\CMS\Controllers\ArticleController;
use App\Modules\CMS\Controllers\BlogController;
use App\Modules\CMS\Controllers\FileController;
use App\Modules\CMS\Controllers\MenuController;
use App\Modules\CMS\Controllers\MenuItemController;
use App\Modules\CMS\Controllers\MetaDefinitionsController;
use App\Modules\CMS\Controllers\MetaDefinitionsFiledsController;
use App\Modules\CMS\Controllers\MetafieldController;
use App\Modules\CMS\Controllers\MetafieldMetaobjectController;
use App\Modules\CMS\Controllers\MetaobjectController;
use App\Modules\CMS\Controllers\PageController;
use App\Modules\Core\Controllers\DashboardStatisticsController;
use App\Modules\Core\Controllers\PartnerNotificationController;
use App\Modules\Core\Controllers\SyncMonitorController;
use App\Modules\Core\Controllers\WebhookLogsController;
use App\Modules\Core\Controllers\WebhookSubscriptionsController;
use App\Modules\Fulfillment\Controllers\FulfillmentController;
use App\Modules\Fulfillment\Controllers\FulfillmentItemController;
use App\Modules\Fulfillment\Controllers\FulfillmentOrderController;
use App\Modules\Fulfillment\Controllers\FulfillmentOrderItemController;
use App\Modules\Fulfillment\Controllers\FulfillmentServiceController;
use App\Modules\Fulfillment\Controllers\FulfillmentTrackingController;
use App\Modules\Fulfillment\Controllers\ReverseFulfillmentController;
use App\Modules\Inventory\Controllers\InventoryController;
use App\Modules\Marketing\Controllers\DiscountCodeController;
use App\Modules\Marketing\Controllers\DiscountController;
use App\Modules\Marketing\Controllers\DiscountUsageController;
use App\Modules\Marketing\Controllers\ExchangeController;
use App\Modules\Marketing\Controllers\MarketController;
use App\Modules\Marketing\Controllers\SellingPlanController;
use App\Modules\Marketing\Controllers\SellingPlanGroupController;
use App\Modules\Marketing\Controllers\SellingPlanSubscriptionController;
use App\Modules\Orders\Controllers\CartController;
use App\Modules\Orders\Controllers\CartItemController;
use App\Modules\Orders\Controllers\DraftOrderController;
use App\Modules\Orders\Controllers\DraftOrderItemController;
use App\Modules\Orders\Controllers\OrderController;
use App\Modules\Orders\Controllers\OrderDutyController;
use App\Modules\Orders\Controllers\OrderItemController;
use App\Modules\Orders\Controllers\OrderItemDutyController;
use App\Modules\Orders\Controllers\OrderReturnController;
use App\Modules\Orders\Controllers\OrderReturnItemController;
use App\Modules\Shipping\Controllers\ShippingZoneController;
use App\Modules\Shopify\Controllers\ShopifyDataSyncController;
use App\Modules\Shopify\Controllers\ShopifyOutboundSyncController;
use App\Modules\Shopify\Controllers\ShopifyProductsSyncController;
use App\Modules\Shopify\Controllers\ShopifyWebhookSecretController;
use App\Modules\Shopify\Controllers\StoreConnectionController;
use App\Modules\Stores\Controllers\AccountsManageController;
use App\Modules\Stores\Controllers\MyStoreController;
use App\Modules\User\Controllers\CustomerController;
use App\Modules\User\Controllers\TeamMemberController;
use App\Modules\User\Controllers\TeamRoleController;
use Illuminate\Http\Request;

/**
 * Catalog of what a store owner can grant a staff role: one section per dashboard tab
 * (with the actions it supports) plus sensitive fields that can be hidden.
 * Permission keys look like "products.view", "orders.delete", "fields.products.prices".
 */
final class StorePermissions
{
    private const CRUD = ['view', 'create', 'edit', 'delete'];
    private const VIEW_EDIT = ['view', 'edit'];
    private const VIEW = ['view'];

    /** section => [group (sidebar menu), label, actions] */
    public const SECTIONS = [
        'dashboard' => ['Dashboard', 'Dashboard', self::VIEW],
        'store-connection' => ['My Store', 'Connect & Sync', ['view', 'edit', 'import']],
        'store-profile' => ['My Store', 'Store Profile', self::VIEW],
        'team-members' => ['Team', 'Team Members', self::CRUD],
        'team-roles' => ['Team', 'Roles', self::CRUD],
        'products' => ['Products', 'All Products', self::CRUD],
        'collections' => ['Products', 'Collections', self::CRUD],
        'vendors' => ['Products', 'Vendors', self::CRUD],
        'product-types' => ['Products', 'Product Types', self::CRUD],
        'options' => ['Products', 'Options', self::CRUD],
        'categories' => ['Products', 'Categories', self::CRUD],
        'tags' => ['Products', 'Tags', self::CRUD],
        'inventory' => ['Products', 'Inventory', self::CRUD],
        'files' => ['Files', 'Files', ['view', 'create', 'delete']],
        'customers' => ['Customers', 'Customers', self::CRUD],
        'shipping' => ['Shipping', 'Shipping', self::CRUD],
        'orders' => ['Orders & Carts', 'All Orders', self::CRUD],
        'draft-orders' => ['Orders & Carts', 'Draft Orders', self::VIEW_EDIT],
        'carts' => ['Orders & Carts', 'Carts', self::VIEW_EDIT],
        'order-duties' => ['Orders & Carts', 'Order Duties', self::VIEW_EDIT],
        'order-item-duties' => ['Orders & Carts', 'Order Item Duties', self::VIEW_EDIT],
        'order-returns' => ['Orders & Carts', 'Order Returns', self::VIEW_EDIT],
        'order-return-items' => ['Orders & Carts', 'Order Return Items', self::VIEW_EDIT],
        'fulfillments' => ['Fulfillments', 'All Fulfillments', self::VIEW_EDIT],
        'fulfillment-orders' => ['Fulfillments', 'Fulfillment Orders', self::VIEW_EDIT],
        'fulfillment-services' => ['Fulfillments', 'Fulfillment Services', self::VIEW_EDIT],
        'reverse-fulfillments' => ['Fulfillments', 'Reverse Fulfillments', self::VIEW_EDIT],
        'payment-transactions' => ['Financials', 'Payment Transactions', self::VIEW_EDIT],
        'refunds' => ['Financials', 'Refunds', self::VIEW_EDIT],
        'discounts' => ['Discounts', 'All Discounts', self::CRUD],
        'discount-codes' => ['Discounts', 'Discount Codes', self::CRUD],
        'discount-usages' => ['Discounts', 'Discount Usages', self::VIEW_EDIT],
        'blogs' => ['Content', 'Blogs', self::CRUD],
        'pages' => ['Content', 'Pages', self::CRUD],
        'menus' => ['Content', 'Menus', self::CRUD],
        'markets' => ['Commerce', 'Markets', self::VIEW],
        'selling-plan-groups' => ['Commerce', 'Selling Plan Groups', self::VIEW],
        'selling-plans' => ['Commerce', 'Selling Plans', self::VIEW],
        'selling-plan-subscriptions' => ['Commerce', 'Selling Plan Subscriptions', self::VIEW],
        'exchanges' => ['Commerce', 'Exchanges', self::VIEW],
        'logs-imports' => ['Logs', 'Imports', self::VIEW],
        'logs-errors' => ['Logs', 'Import Errors', self::VIEW],
        'logs-outbound' => ['Logs', 'Changes Sent to Shopify', self::VIEW_EDIT],
        'logs-webhooks' => ['Logs', 'Shopify Notifications', self::VIEW],
    ];

    /**
     * Fields a role can be allowed to see. Without the permission the keys are removed from
     * the "data" of the listed sections' responses and dropped from their requests.
     * raw_payload is the untouched Shopify object, so it would leak every hidden value.
     *
     * key => [label, response/request keys, sections]
     */
    public const FIELDS = [
        'products.prices' => [
            'Product prices & cost',
            ['price', 'compare_at_price', 'price_min', 'price_max', 'amount', 'compare_at_amount', 'cost', 'raw_payload'],
            ['products', 'inventory'],
        ],
        'inventory.quantities' => [
            'Inventory quantities',
            ['inventory_quantity', 'available_quantity', 'available', 'committed', 'incoming', 'reserved', 'on_hand', 'quantity', 'before_quantity', 'after_quantity', 'raw_payload'],
            ['products', 'inventory'],
        ],
        'customers.contact' => [
            'Customer contact info',
            ['email', 'phone', 'addresses', 'zip', 'raw_payload'],
            ['customers', 'orders', 'draft-orders', 'carts'],
        ],
        'orders.totals' => [
            'Order money totals',
            ['subtotal', 'tax', 'tax_lines', 'shipping', 'discount', 'total', 'total_price', 'unit_price', 'total_spent', 'total_amount', 'next_billing_amount', 'raw_payload'],
            ['orders', 'draft-orders', 'carts', 'customers', 'order-returns', 'order-return-items', 'order-duties', 'order-item-duties'],
        ],
    ];

    /** controller => section */
    private const CONTROLLERS = [
        DashboardStatisticsController::class => 'dashboard',
        ProductsDashboardController::class => 'dashboard',
        MyStoreController::class => 'store-connection',
        StoreConnectionController::class => 'store-connection',
        ShopifyWebhookSecretController::class => 'store-connection',
        ShopifyDataSyncController::class => 'store-connection',
        ShopifyProductsSyncController::class => 'store-connection',
        AccountsManageController::class => 'store-profile',
        TeamMemberController::class => 'team-members',
        TeamRoleController::class => 'team-roles',
        ProductController::class => 'products',
        ProductVariantController::class => 'products',
        MetafieldController::class => 'products',
        MetaobjectController::class => 'products',
        MetaDefinitionsController::class => 'products',
        MetaDefinitionsFiledsController::class => 'products',
        MetafieldMetaobjectController::class => 'products',
        CollectionsController::class => 'collections',
        VendorsController::class => 'vendors',
        ProductTypesController::class => 'product-types',
        OptionsController::class => 'options',
        CategoriesController::class => 'categories',
        TagsController::class => 'tags',
        InventoryController::class => 'inventory',
        FileController::class => 'files',
        CustomerController::class => 'customers',
        ShippingZoneController::class => 'shipping',
        OrderController::class => 'orders',
        OrderItemController::class => 'orders',
        DraftOrderController::class => 'draft-orders',
        DraftOrderItemController::class => 'draft-orders',
        CartController::class => 'carts',
        CartItemController::class => 'carts',
        OrderDutyController::class => 'order-duties',
        OrderItemDutyController::class => 'order-item-duties',
        OrderReturnController::class => 'order-returns',
        OrderReturnItemController::class => 'order-return-items',
        FulfillmentController::class => 'fulfillments',
        FulfillmentItemController::class => 'fulfillments',
        FulfillmentTrackingController::class => 'fulfillments',
        FulfillmentOrderController::class => 'fulfillment-orders',
        FulfillmentOrderItemController::class => 'fulfillment-orders',
        FulfillmentServiceController::class => 'fulfillment-services',
        ReverseFulfillmentController::class => 'reverse-fulfillments',
        PaymentTransactionController::class => 'payment-transactions',
        RefundController::class => 'refunds',
        RefundItemController::class => 'refunds',
        DiscountController::class => 'discounts',
        DiscountCodeController::class => 'discount-codes',
        DiscountUsageController::class => 'discount-usages',
        BlogController::class => 'blogs',
        ArticleController::class => 'blogs',
        PageController::class => 'pages',
        MenuController::class => 'menus',
        MenuItemController::class => 'menus',
        MarketController::class => 'markets',
        SellingPlanGroupController::class => 'selling-plan-groups',
        SellingPlanController::class => 'selling-plans',
        SellingPlanSubscriptionController::class => 'selling-plan-subscriptions',
        ExchangeController::class => 'exchanges',
        SyncMonitorController::class => 'logs-imports',
        ShopifyOutboundSyncController::class => 'logs-outbound',
        WebhookLogsController::class => 'logs-webhooks',
        WebhookSubscriptionsController::class => 'logs-webhooks',
    ];

    /** Controller methods that are not plain index/show/store/update/destroy. */
    private const METHOD_ACTIONS = [
        'index' => 'view',
        'show' => 'view',
        'statistics' => 'view',
        'catalog' => 'view',
        'partner' => 'view',
        'status' => 'view',
        'syncStatus' => 'view',
        'store' => 'create',
        'createFromAdmin' => 'create',
        'uploadToShopify' => 'create',
        'attach' => 'create',
        'update' => 'edit',
        'changeStatus' => 'edit',
        'updateVariantsPrice' => 'edit',
        'connect' => 'edit',
        'test' => 'edit',
        'verify' => 'edit',
        'SaveShopifyStoreDetails' => 'edit',
        'retry' => 'edit',
        'queue' => 'edit',
        'dispatchDue' => 'edit',
        'destroy' => 'delete',
        'destroyProductMedia' => 'delete',
    ];

    /** Pages every staff member keeps: their own account and notifications. */
    private const ALWAYS_ALLOWED = [
        PartnerNotificationController::class,
    ];

    /**
     * The permission a staff request needs: "section.action", '' when it needs none,
     * or null when the route is not part of the store dashboard (deny for staff).
     */
    public static function requiredFor(Request $request): ?string
    {
        $action = $request->route()?->getActionName() ?? '';

        if (!str_contains($action, '@')) {
            return null;
        }

        [$controller, $method] = explode('@', $action);

        if (in_array($controller, self::ALWAYS_ALLOWED, true)) {
            return '';
        }

        $section = self::CONTROLLERS[$controller] ?? null;

        if ($controller === SyncMonitorController::class && $request->route('type') === 'errors') {
            $section = 'logs-errors';
        }

        if ($controller === ShopifyDataSyncController::class || $controller === ShopifyProductsSyncController::class) {
            return $section ? "{$section}.import" : null;
        }

        $verb = self::METHOD_ACTIONS[$method] ?? null;

        return $section && $verb ? "{$section}.{$verb}" : null;
    }

    public static function sectionOf(?string $permission): ?string
    {
        return $permission ? explode('.', $permission)[0] : null;
    }

    /**
     * Every grantable key, used to validate what a role is saved with.
     *
     * @return array<int, string>
     */
    public static function allKeys(): array
    {
        $keys = [];

        foreach (self::SECTIONS as $section => [, , $actions]) {
            foreach ($actions as $action) {
                $keys[] = "{$section}.{$action}";
            }
        }

        foreach (array_keys(self::FIELDS) as $field) {
            $keys[] = "fields.{$field}";
        }

        return $keys;
    }

    /**
     * The catalog as the role editor shows it.
     *
     * @return array<string, mixed>
     */
    public static function catalog(): array
    {
        return [
            'sections' => collect(self::SECTIONS)
                ->map(fn ($section, $key) => ['key' => $key, 'group' => $section[0], 'label' => $section[1], 'actions' => $section[2]])
                ->values()
                ->all(),
            'fields' => collect(self::FIELDS)
                ->map(fn ($field, $key) => ['key' => "fields.{$key}", 'label' => $field[0], 'keys' => array_values(array_diff($field[1], ['raw_payload'])), 'sections' => $field[2]])
                ->values()
                ->all(),
        ];
    }

    /**
     * section => hidden keys, so the dashboard can drop the matching inputs and columns.
     *
     * @param array<int, string> $granted
     * @return array<string, array<int, string>>
     */
    public static function hiddenFieldsBySection(array $granted): array
    {
        $sections = array_unique(array_merge(...array_column(self::FIELDS, 2)));
        $hidden = [];

        foreach ($sections as $section) {
            $keys = array_values(array_diff(self::hiddenKeys($section, $granted), ['raw_payload']));

            if ($keys !== []) {
                $hidden[$section] = $keys;
            }
        }

        return $hidden;
    }

    /**
     * Response/request keys hidden from someone with these permissions on a section.
     *
     * @param array<int, string> $granted
     * @return array<int, string>
     */
    public static function hiddenKeys(string $section, array $granted): array
    {
        $hidden = [];

        foreach (self::FIELDS as $field => [, $keys, $sections]) {
            if (in_array($section, $sections, true) && !in_array("fields.{$field}", $granted, true)) {
                $hidden = array_merge($hidden, $keys);
            }
        }

        return array_values(array_unique($hidden));
    }
}
