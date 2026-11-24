<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Store team: a store owner adds staff users and gives them store-scoped roles.
 * - users.store_id: the store a staff member works for (null for owners and admins).
 * - plans.max_team_members: how many staff a plan allows (0 = none).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('store_id')->nullable()->after('id')->index();
            $table->foreign('store_id')->references('id')->on('stores')->cascadeOnDelete();
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->unsignedInteger('max_team_members')->default(0)->after('trial_days');
            $table->text('description')->nullable()->after('name');
            $table->json('features')->nullable()->after('max_team_members');
        });

        Schema::create('store_roles', function (Blueprint $table) {
            $table->id();
            $table->uuid('store_id')->index();
            $table->string('name');
            $table->json('permissions');
            $table->timestamps();

            $table->unique(['store_id', 'name']);
            $table->foreign('store_id')->references('id')->on('stores')->cascadeOnDelete();
        });

        Schema::create('store_role_user', function (Blueprint $table) {
            $table->foreignId('store_role_id')->constrained('store_roles')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->primary(['store_role_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_role_user');
        Schema::dropIfExists('store_roles');

        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['max_team_members', 'description', 'features']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['store_id']);
            $table->dropColumn('store_id');
        });
    }
};
