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
        ->assertJsonPath('theme.light.accent', '#80632c');
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

it('rejects trailing newline and backslash tricks', function () {
    Sanctum::actingAs(appearanceUser('admin'));
    // TrimStrings strips a trailing newline over HTTP, so assert the regexes directly (defence in depth).
    expect(preg_match(\App\Modules\Appearance\Support\AppearanceDefaults::HEX_REGEX, "#112233\n"))->toBe(0)
        ->and(preg_match(\App\Modules\Appearance\Support\AppearanceDefaults::LINK_REGEX, "/\\evil.com"))->toBe(0);
    $this->putJson('/api/admin/appearance/theme', ['logo_url' => '/\\evil.com'])->assertStatus(422);
});

it('rejects theme urls the css sanitiser would drop', function () {
    Sanctum::actingAs(appearanceUser('admin'));
    foreach (['/images/logo(1).png', "/a'b.png", '/a"b.png', '/a b.png'] as $url) {
        $this->putJson('/api/admin/appearance/theme', ['logo_url' => $url])->assertStatus(422);
    }
    $this->putJson('/api/admin/appearance/theme', ['favicon_url' => '/images/logo(1).png'])->assertStatus(422);
    $this->putJson('/api/admin/appearance/theme', ['logo_url' => 'https://cdn.test/logo-1.png'])->assertOk();
});

function section(array $o = []): array
{
    return array_merge(['id' => 's1', 'type' => 'hero_paths', 'visible' => true, 'props' => [], 'i18n' => []], $o);
}

it('saves and returns sections in order', function () {
    Sanctum::actingAs(appearanceUser('admin'));
    $this->putJson('/api/admin/appearance/home-sections', ['sections' => [section(['id' => 'b']), section(['id' => 'a', 'type' => 'faq'])]])
        ->assertOk()->assertJsonPath('0.id', 'b');
    $this->getJson('/api/site/appearance')->assertJsonPath('home_sections.0.id', 'b')->assertJsonPath('home_sections.1.id', 'a');
});

it('rejects unknown section types', function () {
    Sanctum::actingAs(appearanceUser('admin'));
    $this->putJson('/api/admin/appearance/home-sections', ['sections' => [section(['type' => 'nope'])]])->assertStatus(422);
});

it('rejects javascript links anywhere', function () {
    Sanctum::actingAs(appearanceUser('admin'));
    $this->putJson('/api/admin/appearance/home-sections', ['sections' => [section(['props' => ['cards' => [['link' => 'javascript:x']]]])]])->assertStatus(422);
    $this->putJson('/api/admin/appearance/home-sections', ['sections' => [section(['props' => ['color' => 'red']])]])->assertStatus(422);
});

it('keys sanitizer errors by section index', function () {
    Sanctum::actingAs(appearanceUser('admin'));
    $this->putJson('/api/admin/appearance/home-sections', ['sections' => [
        section(),
        section(['id' => 's2', 'type' => 'feature_cards', 'props' => ['cards' => [['link' => 'javascript:x']]]]),
    ]])->assertStatus(422)->assertJsonValidationErrors(['sections.1.props.cards.0.link']);
    $this->putJson('/api/admin/appearance/home-sections', ['sections' => [section(['i18n' => ['ar' => ['color' => 'red']]])]])
        ->assertStatus(422)->assertJsonValidationErrors(['sections.0.i18n.ar.color']);
});

it('strips html from text', function () {
    Sanctum::actingAs(appearanceUser('admin'));
    $this->putJson('/api/admin/appearance/home-sections', ['sections' => [section(['i18n' => ['en' => ['headline' => '<b>Hi</b><script>x</script>']]])]])
        ->assertOk()->assertJsonPath('0.i18n.en.headline', 'Hix');
});

it('rejects unknown languages', function () {
    Sanctum::actingAs(appearanceUser('admin'));
    $this->putJson('/api/admin/appearance/home-sections', ['sections' => [section(['i18n' => ['fr' => ['headline' => 'x']]])]])->assertStatus(422);
});

it('rejects control characters and backslash tricks in links', function () {
    Sanctum::actingAs(appearanceUser('admin'));
    $this->putJson('/api/admin/appearance/theme', ['logo_url' => "/\t/evil.com"])->assertStatus(422);
    foreach (["/\t/evil.com", '/\evil.com'] as $link) {
        $this->putJson('/api/admin/appearance/home-sections', ['sections' => [section(['props' => ['link' => $link]])]])->assertStatus(422);
    }
});
