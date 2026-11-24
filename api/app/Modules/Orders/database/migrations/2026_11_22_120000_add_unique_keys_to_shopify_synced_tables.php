<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The Shopify sync upserts these rows with updateOrCreate(), which is a SELECT
 * then an INSERT. Without a unique key, a webhook and a bulk sync (or one job
 * picked up twice) both insert, so orders got duplicate items, transactions,
 * refunds and fulfillments.
 *
 * Existing duplicates are merged, not just dropped: the oldest row survives
 * (it is the one updateOrCreate keeps finding), every row that points at a
 * duplicate is re-pointed to the survivor, and only then are the copies deleted.
 * Parents run before their children so re-pointed children are merged too.
 */
return new class extends Migration
{
    /** [table, upsert key, index name, rows that reference this table's id] */
    private function plan(): array
    {
        return [
            ['order_items', ['order_id', 'shopify_line_item_id'], 'order_items_order_line_unique', [
                ['tax_lines', 'order_item_id'],
                ['fulfillment_order_items', 'order_item_id'],
                ['fulfillment_items', 'order_item_id'],
                ['order_return_items', 'order_item_id'],
                ['order_item_duties', 'order_item_id'],
            ]],
            ['draft_orders', ['store_id', 'shopify_draft_order_id'], 'draft_orders_store_shopify_unique', [
                ['draft_order_items', 'draft_order_id'],
            ]],
            ['refunds', ['store_id', 'shopify_refund_id'], 'refunds_store_shopify_unique', [
                ['refund_items', 'refund_id'],
                ['payment_transactions', 'refund_id'],
            ]],
            ['fulfillments', ['store_id', 'shopify_fulfillment_id'], 'fulfillments_store_shopify_unique', [
                ['fulfillment_items', 'fulfillment_id'],
                ['fulfillment_tracking', 'fulfillment_id'],
            ]],
            ['draft_order_items', ['draft_order_id', 'shopify_line_item_id'], 'draft_order_items_draft_line_unique', []],
            ['refund_items', ['refund_id', 'shopify_refund_line_item_id'], 'refund_items_refund_line_unique', []],
            ['fulfillment_items', ['fulfillment_id', 'shopify_line_item_id'], 'fulfillment_items_fulfillment_line_unique', []],
            ['payment_transactions', ['store_id', 'shopify_transaction_id'], 'payment_transactions_store_shopify_unique', []],
            // shortcut: order-level tax lines have order_item_id NULL and MySQL treats NULLs as
            // distinct, so only item-level lines are protected; store 0 instead of NULL to cover both.
            ['tax_lines', ['order_id', 'order_item_id', 'source_key'], 'tax_lines_order_item_source_unique', []],
            ['order_risks', ['store_id', 'shopify_order_id', 'assessment_id'], 'order_risks_store_order_assessment_unique', []],
        ];
    }

    public function up(): void
    {
        foreach ($this->plan() as [$table, $key, $index, $refs]) {
            if (!Schema::hasTable($table) || !$this->hasColumns($table, $key)) {
                continue;
            }

            DB::transaction(fn () => $this->mergeDuplicates($table, $key, $refs));

            Schema::table($table, fn (Blueprint $t) => $t->unique($key, $index));
        }

        // The plain index on the same columns is now redundant.
        if (Schema::hasIndex('order_risks', 'order_risks_store_order_assessment_idx')) {
            Schema::table('order_risks', fn (Blueprint $t) => $t->dropIndex('order_risks_store_order_assessment_idx'));
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('order_risks') && !Schema::hasIndex('order_risks', 'order_risks_store_order_assessment_idx')) {
            Schema::table('order_risks', fn (Blueprint $t) => $t->index(['store_id', 'shopify_order_id', 'assessment_id'], 'order_risks_store_order_assessment_idx'));
        }

        foreach (array_reverse($this->plan()) as [$table, $key, $index]) {
            if (!Schema::hasTable($table) || !Schema::hasIndex($table, $index)) {
                continue;
            }
            // MySQL drops a foreign key's own index once the unique key can serve it, and then
            // refuses to drop the unique key. Give the leading column its own index back first.
            $covered = collect(Schema::getIndexes($table))
                ->contains(fn ($i) => $i['name'] !== $index && ($i['columns'][0] ?? null) === $key[0]);
            Schema::table($table, function (Blueprint $t) use ($covered, $key, $index) {
                if (!$covered) {
                    $t->index($key[0]);
                }
                $t->dropUnique($index);
            });
        }
        // Merged duplicates are not restored: they were copies of the surviving row.
    }

    private function mergeDuplicates(string $table, array $key, array $refs): void
    {
        // Rows with a NULL key part never collide under a unique index, so leave them alone.
        $groups = DB::table($table)
            ->select($key)
            ->selectRaw('MIN(id) as keep_id, COUNT(*) as n')
            ->where(fn ($q) => array_map(fn ($c) => $q->whereNotNull($c), $key))
            ->groupBy($key)
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($groups as $group) {
            $dupes = DB::table($table)
                ->where(fn ($q) => array_map(fn ($c) => $q->where($c, $group->{$c}), $key))
                ->where('id', '!=', $group->keep_id)
                ->pluck('id');

            foreach ($dupes->chunk(500) as $chunk) {
                foreach ($refs as [$refTable, $refColumn]) {
                    if (Schema::hasTable($refTable) && Schema::hasColumn($refTable, $refColumn)) {
                        DB::table($refTable)->whereIn($refColumn, $chunk)->update([$refColumn => $group->keep_id]);
                    }
                }
                DB::table($table)->whereIn('id', $chunk)->delete();
            }
        }
    }

    private function hasColumns(string $table, array $columns): bool
    {
        foreach ($columns as $column) {
            if (!Schema::hasColumn($table, $column)) {
                return false;
            }
        }

        return true;
    }
};
