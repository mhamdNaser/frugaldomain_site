<?php

namespace Tests\Feature;

use App\Modules\Core\Models\DownloadEvent;
use App\Modules\Core\Models\PageVisit;
use App\Modules\DesktopSoftware\Models\DesktopSoftware;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AnalyticsInsightsTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email, ?string $role = null): User
    {
        $user = User::unguarded(fn () => User::create([
            'name' => $email,
            'first_name' => 'Test',
            'email' => $email,
            'password' => bcrypt('secret-password'),
        ]));
        if ($role) {
            $user->assignRole(Role::findOrCreate($role));
        }

        return $user;
    }

    private function visit(string $path, string $session, ?string $referrer = null): void
    {
        $this->postJson('/api/track/visit', [
            'path' => $path,
            'session_id' => $session,
            'referrer' => $referrer,
        ]);
    }

    public function test_admin_traffic_is_never_counted_even_after_sign_out(): void
    {
        $admin = $this->user('admin@example.com', 'admin');

        Sanctum::actingAs($admin);
        $this->visit('/learn', 'admin-browser');
        $this->assertSame(0, PageVisit::count());
        $this->assertTrue(DB::table('analytics_excluded_sessions')->where('session_id', 'admin-browser')->exists());

        // Signed out, same browser: still the admin, still not counted.
        $this->app['auth']->forgetGuards();
        $this->visit('/IconsGalary', 'admin-browser');
        $this->assertSame(0, PageVisit::count());

        // A real visitor is counted.
        $this->visit('/IconsGalary', 'visitor-1');
        $this->assertSame(1, PageVisit::count());
    }

    public function test_rows_recorded_before_the_rule_are_hidden_from_reports(): void
    {
        $admin = $this->user('old-admin@example.com', 'super-admin');
        PageVisit::withoutGlobalScopes()->insert([
            ['user_id' => $admin->id, 'session_id' => 'old-admin', 'path' => '/', 'visited_at' => now()],
            ['user_id' => null, 'session_id' => 'visitor', 'path' => '/', 'visited_at' => now()],
        ]);

        $this->assertSame(1, PageVisit::count());
    }

    public function test_the_overview_reports_sections_sources_and_engagement(): void
    {
        $this->visit('/learn/networking-foundations-ccna/cdp-and-lldp', 'v1', 'https://www.google.com/');
        $this->visit('/learn/networking-foundations-ccna', 'v1');
        $this->visit('/ar/ImageConvert', 'v2', 'https://chatgpt.com/');
        $this->visit('/json-toolkit', 'v3');

        $admin = $this->user('dash@example.com', 'admin');
        Sanctum::actingAs($admin);

        $data = $this->withHeader('X-Session-Id', 'dash-browser')
            ->getJson('/api/admin/analytics/overview?days=30')
            ->assertOk()
            ->json('data');

        $sections = collect($data['sections'])->keyBy('section');
        $this->assertSame(2, $sections['learn']['visits']);
        $this->assertSame(1, $sections['converters']['visits']);
        $this->assertSame(1, $sections['dev_tools']['visits']);

        $channels = collect($data['traffic_sources']['channels'])->pluck('total', 'label');
        $this->assertSame(1, $channels['search']);
        $this->assertSame(1, $channels['ai']);
        $this->assertSame(1, $channels['direct']);

        $this->assertSame(3, $data['engagement']['visitors']);
        $this->assertSame(3, $data['engagement']['new_visitors']);
        $this->assertSame('networking-foundations-ccna', $data['learning']['courses'][0]['course']);
        $this->assertContains('/learn/networking-foundations-ccna/cdp-and-lldp', collect($data['entry_exit_pages']['entry'])->pluck('path')->all());
        $this->assertContains('/learn/networking-foundations-ccna', collect($data['entry_exit_pages']['exit'])->pluck('path')->all());

        // The dashboard's own browser is now known as staff.
        $this->assertTrue(DB::table('analytics_excluded_sessions')->where('session_id', 'dash-browser')->exists());
    }

    public function test_program_downloads_are_recorded_and_staff_downloads_are_not(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('desktop-software/tool.zip', 'bytes');
        DesktopSoftware::create([
            'slug' => 'tool',
            'name' => 'Tool',
            'summary' => 'A desktop utility.',
            'readme' => "Install it.
",
            'platform' => 'windows',
            'environment' => '.NET 8',
            'programming_language' => 'C#',
            'is_open_source' => true,
            'version' => '1.0.0',
            'status' => 'published',
            'file_path' => 'desktop-software/tool.zip',
            'ordering' => 0,
        ]);

        $this->get('/api/desktop-apps/tool/download?sid=visitor-9')->assertOk();
        $this->assertSame(1, DownloadEvent::count());
        $this->assertSame('visitor-9', DownloadEvent::first()->session_id);

        DB::table('analytics_excluded_sessions')->insert(['session_id' => 'staff-browser', 'created_at' => now(), 'updated_at' => now()]);
        $this->get('/api/desktop-apps/tool/download?sid=staff-browser')->assertOk();
        $this->assertSame(1, DownloadEvent::count());

        $admin = $this->user('stats@example.com', 'admin');
        Sanctum::actingAs($admin);
        $downloads = $this->getJson('/api/admin/analytics/downloads')->assertOk()->json('data');
        $this->assertSame(1, $downloads['period']);
        $this->assertSame('tool', $downloads['items'][0]['slug']);
    }
}
