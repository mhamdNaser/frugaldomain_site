<?php

use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function appearanceUser(?string $role = null): User
{
    $user = User::unguarded(fn () => User::create([
        'name' => 'T', 'first_name' => 'T', 'email' => uniqid().'@example.com', 'password' => bcrypt('secret-password'),
    ]));
    if ($role) {
        $user->assignRole(Role::findOrCreate($role));
    }

    return $user;
}

it('returns defaults when nothing is saved', function () {
    $cc = $this->getJson('/api/site/appearance')->headers->get('Cache-Control');
    expect($cc)->toContain('max-age=60')->toContain('public');
    $this->getJson('/api/site/appearance')->assertOk()
        ->assertJsonPath('theme.dark.accent', '#c9a96e')
        ->assertJsonPath('theme.font_body', 'IBM Plex Sans Arabic')
        ->assertJsonPath('home_sections', [])
        ->assertJsonPath('version', 0);
});

it('lets an admin save the theme', function () {
    Sanctum::actingAs(appearanceUser('admin'));
    $this->putJson('/api/admin/appearance/theme', ['dark' => ['accent' => '#112233']])
        ->assertOk()->assertJsonPath('dark.accent', '#112233');

    $this->getJson('/api/site/appearance')->assertOk()
        ->assertJsonPath('theme.dark.accent', '#112233')
        ->assertJsonPath('theme.dark.canvas', '#0a0f1c')
        ->assertJsonPath('theme.light.accent', '#9a7a3f');
});

it('rejects non-admins', function () {
    $this->putJson('/api/admin/appearance/theme', [])->assertUnauthorized();
    Sanctum::actingAs(appearanceUser());
    $this->putJson('/api/admin/appearance/theme', [])->assertForbidden();
});

it('validates colours and fonts', function () {
    Sanctum::actingAs(appearanceUser('admin'));
    foreach ([
        ['dark' => ['accent' => 'red']],
        ['dark' => ['accent' => '#12345']],
        ['font_body' => 'Comic Sans'],
        ['logo_url' => 'javascript:alert(1)'],
        ['radius' => 99],
        ['default_mode' => 'blue'],
    ] as $payload) {
        $this->putJson('/api/admin/appearance/theme', $payload)->assertStatus(422);
    }
    $this->putJson('/api/admin/appearance/theme', ['logo_url' => '/images/logo.svg'])->assertOk();
});
