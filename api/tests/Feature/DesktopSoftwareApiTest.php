<?php

namespace Tests\Feature;

use App\Modules\DesktopSoftware\Models\DesktopSoftware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DesktopSoftwareApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_public_catalogue_only_lists_published_downloads(): void
    {
        DesktopSoftware::create($this->software('released-tool'));
        DesktopSoftware::create($this->software('draft-tool', ['status' => 'draft']));

        $response = $this->getJson('/api/desktop-apps')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'released-tool')
            ->assertJsonPath('data.0.platform', 'windows')
            ->assertJsonPath('data.0.environment', '.NET 8')
            ->assertJsonPath('data.0.programming_language', 'C#')
            ->assertJsonPath('data.0.is_open_source', true);

        $this->assertStringEndsWith(
            '/api/desktop-apps/released-tool/download',
            $response->json('data.0.download_url')
        );
    }

    public function test_published_downloads_are_served_as_attachments(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('desktop-software/released-tool.zip', 'installer bytes');
        DesktopSoftware::create($this->software('released-tool'));

        $this->get('/api/desktop-apps/released-tool/download')
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=released-tool.zip');
    }

    public function test_published_software_image_is_available_and_listed_in_the_catalogue(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('desktop-software/images/released-tool.png', 'fake image bytes');
        DesktopSoftware::create($this->software('released-tool', [
            'image_path' => 'desktop-software/images/released-tool.png',
        ]));

        $this->getJson('/api/desktop-apps')
            ->assertOk()
            ->assertJsonPath('data.0.image_url', url('/api/desktop-apps/released-tool/image'));

        $this->get('/api/desktop-apps/released-tool/image')
            ->assertOk()
            ->assertHeader('content-type', 'image/png');
    }

    public function test_draft_software_image_is_not_publicly_accessible(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('desktop-software/images/draft-tool.png', 'fake image bytes');
        DesktopSoftware::create($this->software('draft-tool', [
            'status' => 'draft',
            'image_path' => 'desktop-software/images/draft-tool.png',
        ]));

        $this->get('/api/desktop-apps/draft-tool/image')->assertNotFound();
    }

    public function test_draft_downloads_are_not_publicly_accessible(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('desktop-software/draft-tool.zip', 'installer bytes');
        DesktopSoftware::create($this->software('draft-tool', ['status' => 'draft']));

        $this->getJson('/api/desktop-apps/draft-tool')->assertNotFound();
        $this->get('/api/desktop-apps/draft-tool/download')->assertNotFound();
    }

    private function software(string $slug, array $overrides = []): array
    {
        return array_merge([
            'slug' => $slug,
            'name' => 'Released Tool',
            'summary' => 'A desktop utility.',
            'readme' => "Install the downloaded package.\n",
            'platform' => 'windows',
            'platform_name' => null,
            'environment' => '.NET 8',
            'programming_language' => 'C#',
            'is_open_source' => true,
            'version' => '1.0.0',
            'status' => 'published',
            'file_path' => "desktop-software/{$slug}.zip",
            'ordering' => 0,
        ], $overrides);
    }
}
