<?php

namespace Tests\Feature;

use App\Modules\Rating\Models\SiteRating;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteRatingApiTest extends TestCase
{
    use RefreshDatabase;

    private const VISITOR = '0b6c1d2e-3f40-4a5b-8c7d-9e0f1a2b3c4d';

    public function test_a_rating_is_counted_and_a_second_one_replaces_it(): void
    {
        $this->postJson('/api/ratings', ['path' => '/json-toolkit', 'stars' => 2, 'visitor' => self::VISITOR, 'locale' => 'hi'])
            ->assertCreated()->assertJson(['average' => 2, 'count' => 1]);

        $this->postJson('/api/ratings', ['path' => '/json-toolkit', 'stars' => 5, 'comment' => 'Great', 'visitor' => self::VISITOR])
            ->assertCreated()->assertJson(['average' => 5, 'count' => 1]);

        $this->postJson('/api/ratings', ['path' => '/json-toolkit', 'stars' => 4, 'visitor' => 'another-visitor-id-1234'])
            ->assertJson(['average' => 4.5, 'count' => 2]);

        $this->getJson('/api/ratings?path=/json-toolkit')->assertOk()->assertJson(['average' => 4.5, 'count' => 2]);
        $this->getJson('/api/ratings?path=/tools')->assertOk()->assertJson(['average' => null, 'count' => 0]);
        $this->assertSame('Great', SiteRating::where('stars', 5)->value('comment'));
        $this->assertNotSame(self::VISITOR, SiteRating::first()->getRawOriginal('fingerprint'));
    }

    public function test_bad_input_is_rejected(): void
    {
        foreach ([
            ['path' => 'https://evil.example', 'stars' => 5, 'visitor' => self::VISITOR],
            ['path' => '/tools', 'stars' => 6, 'visitor' => self::VISITOR],
            ['path' => '/tools', 'stars' => 3, 'visitor' => 'short'],
            ['path' => '/tools', 'stars' => 3, 'visitor' => self::VISITOR, 'locale' => 'fr'],
        ] as $payload) {
            $this->postJson('/api/ratings', $payload)->assertStatus(422);
        }
        $this->getJson('/api/ratings?path=../etc')->assertStatus(422);
        $this->assertSame(0, SiteRating::count());
    }
}
