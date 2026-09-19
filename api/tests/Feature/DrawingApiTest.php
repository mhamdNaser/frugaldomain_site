<?php

namespace Tests\Feature;

use App\Modules\Drawing\Models\Drawing;
use App\Modules\Drawing\Services\DrawingModerationService;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * The HTTP surface: who can reach what.
 *
 * Access rules are only real if they hold at the endpoint, so these go
 * through the routes rather than calling the services directly.
 */
class DrawingApiTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email): User
    {
        $handle = explode('@', $email)[0];

        return User::create([
            'name' => $handle,
            'first_name' => $handle,
            'medium_name' => '',
            'last_name' => 'Tester',
            'email' => $email,
            'phone' => random_int(1000000000, 9999999999),
            'password' => bcrypt('secret-for-tests'),
            'status' => 1,
        ]);
    }

    private function validDocument(): array
    {
        return [
            'paths' => [['type' => 'rect', 'x' => 0, 'y' => 0, 'width' => 10, 'height' => 10]],
            'textItems' => [],
            'width' => 200,
            'height' => 150,
            'background' => '#ffffff',
        ];
    }

    public function test_saving_a_drawing_requires_signing_in(): void
    {
        $this->postJson('/api/drawings', [
            'title' => 'Anonymous attempt',
            'document' => $this->validDocument(),
        ])->assertUnauthorized();
    }

    public function test_a_signed_in_user_can_save_and_reopen_a_drawing(): void
    {
        Sanctum::actingAs($this->user('artist@test.com'));

        $response = $this->postJson('/api/drawings', [
            'title' => 'My first piece',
            'document' => $this->validDocument(),
        ])->assertCreated();

        $id = $response->json('data.id');

        // A freshly saved drawing is private, never public.
        $this->assertSame(Drawing::STATUS_PRIVATE, $response->json('data.status'));

        $this->getJson("/api/drawings/{$id}")
            ->assertOk()
            // The document comes back so the editor can reopen it.
            ->assertJsonPath('data.document.width', 200);
    }

    public function test_one_user_cannot_read_or_change_another_users_drawing(): void
    {
        $owner = $this->user('owner@test.com');
        $stranger = $this->user('stranger@test.com');

        Sanctum::actingAs($owner);
        $id = $this->postJson('/api/drawings', [
            'title' => 'Private work',
            'document' => $this->validDocument(),
        ])->json('data.id');

        Sanctum::actingAs($stranger);
        $this->getJson("/api/drawings/{$id}")->assertNotFound();
        $this->putJson("/api/drawings/{$id}", [
            'title' => 'Hijacked',
            'document' => $this->validDocument(),
        ])->assertNotFound();
        $this->deleteJson("/api/drawings/{$id}")->assertNotFound();
    }

    public function test_publishing_only_queues_for_review(): void
    {
        Sanctum::actingAs($this->user('artist@test.com'));

        $id = $this->postJson('/api/drawings', [
            'title' => 'Please publish me',
            'document' => $this->validDocument(),
            'submit_for_review' => true,
        ])->json('data.id');

        // Pending, not published: the gallery must stay empty until an admin
        // passes it.
        $this->assertSame(Drawing::STATUS_PENDING, Drawing::find($id)->status);
        $this->getJson('/api/drawings/gallery')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_the_gallery_shows_a_drawing_only_after_approval(): void
    {
        $author = $this->user('author@test.com');
        Sanctum::actingAs($author);

        $id = $this->postJson('/api/drawings', [
            'title' => 'Gallery piece',
            'document' => $this->validDocument(),
            'submit_for_review' => true,
        ])->json('data.id');

        app(DrawingModerationService::class)->approve(Drawing::find($id), $author->id);

        $this->getJson('/api/drawings/gallery')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Gallery piece');
    }

    public function test_editing_a_published_drawing_sends_it_back_for_review(): void
    {
        $author = $this->user('author@test.com');
        Sanctum::actingAs($author);

        $id = $this->postJson('/api/drawings', [
            'title' => 'Approved once',
            'document' => $this->validDocument(),
        ])->json('data.id');

        app(DrawingModerationService::class)->approve(Drawing::find($id), $author->id);
        $this->assertSame(Drawing::STATUS_PUBLISHED, Drawing::find($id)->status);

        // Swapping the contents of a live drawing must not stay live.
        $this->putJson("/api/drawings/{$id}", [
            'title' => 'Something else entirely',
            'document' => $this->validDocument(),
        ])->assertOk();

        $this->assertSame(Drawing::STATUS_PENDING, Drawing::find($id)->status);
        $this->getJson('/api/drawings/gallery')->assertJsonCount(0, 'data');
    }

    public function test_the_moderation_queue_is_closed_to_ordinary_users(): void
    {
        Sanctum::actingAs($this->user('nobody@test.com'));

        $this->getJson('/api/admin/drawings/queue')->assertForbidden();
    }

    public function test_an_oversized_document_is_rejected(): void
    {
        Sanctum::actingAs($this->user('artist@test.com'));

        $document = $this->validDocument();
        // Simulate a drawing stuffed with image data beyond the ceiling.
        $document['paths'][] = ['type' => 'image', 'href' => str_repeat('x', 9 * 1024 * 1024)];

        $this->postJson('/api/drawings', [
            'title' => 'Far too big',
            'document' => $document,
        ])->assertStatus(422)->assertJsonValidationErrors('document');
    }
}
