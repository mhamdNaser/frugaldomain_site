<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Payment methods the super admin offers for paid plans (the table existed as an
 * empty placeholder), the plan <-> method link, and the subscription's method.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->string('name')->default('');
            $table->string('driver', 50)->default('manual')->index();
            $table->text('config')->nullable();          // encrypted JSON, driver settings
            $table->json('instructions')->nullable();    // per-language text shown at checkout
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort')->default(0);
        });

        Schema::create('plan_payment_method', function (Blueprint $table) {
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_method_id')->constrained()->cascadeOnDelete();
            $table->primary(['plan_id', 'payment_method_id']);
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->foreignId('payment_method_id')->nullable()->after('plan_id')->constrained()->nullOnDelete();
            $table->string('reference')->nullable();
        });

        // MySQL ignores the "where status = active" part, so this index allowed only one
        // canceled (or pending) row per store forever. One active row is enforced in code.
        if ($this->hasIndex('subscriptions', 'unique_active_subscription')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->dropUnique('unique_active_subscription');
            });
        }
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_method_id');
            $table->dropColumn('reference');
        });
        Schema::dropIfExists('plan_payment_method');
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->dropColumn(['name', 'driver', 'config', 'instructions', 'is_active', 'sort']);
        });
    }

    private function hasIndex(string $table, string $index): bool
    {
        return collect(Schema::getIndexes($table))->contains(fn ($i) => $i['name'] === $index);
    }
};
