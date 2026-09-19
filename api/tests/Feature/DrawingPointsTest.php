<?php

namespace Tests\Feature;

use App\Modules\Drawing\Models\CreatorPointEntry;
use App\Modules\Drawing\Models\Drawing;
use App\Modules\Drawing\Services\DrawingModerationService;
use App\Modules\Drawing\Services\DrawingUsageRecorder;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The rules that decide whether points are earned.
 *
 * These are the parts of the feature worth attacking, since the points are
 * meant to become money, so they are tested directly rather than only through
 * the happy path.
 */
class DrawingPointsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Created directly rather than through a factory: the User model lives in
     * a module namespace and has no factory registered for it.
     */
    private function user(string $email): User
    {
        $handle = explode('@', $email)[0];

        // Every column the users table requires; it has no nullable defaults.
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

    private function publishedDrawing(User $author): Drawing
    {
        $drawing = Drawing::create([
            'user_id' => $author->id,
            'title' => 'A shared shape',
            'document' => ['paths' => [], 'textItems' => [], 'width' => 100, 'height' => 100],
            'width' => 100,
            'height' => 100,
            'status' => Drawing::STATUS_PRIVATE,
        ]);

        app(DrawingModerationService::class)->approve($drawing, $author->id);

        return $drawing->refresh();
    }

    public function test_a_first_use_by_someone_else_credits_the_author(): void
    {
        $author = $this->user('author@test.com');
        $other = $this->user('other@test.com');
        $drawing = $this->publishedDrawing($author);

        $result = app(DrawingUsageRecorder::class)->record($drawing, $other->id);

        $this->assertTrue($result['counted']);
        $this->assertSame(1, $drawing->refresh()->use_count);

        // The publish bonus plus the one usage point.
        $this->assertSame(
            DrawingModerationService::PUBLISH_BONUS + DrawingUsageRecorder::POINTS_PER_USAGE,
            CreatorPointEntry::balanceFor($author->id)
        );
    }

    public function test_the_same_user_cannot_earn_the_author_a_second_point(): void
    {
        $author = $this->user('author@test.com');
        $other = $this->user('other@test.com');
        $drawing = $this->publishedDrawing($author);
        $recorder = app(DrawingUsageRecorder::class);

        $recorder->record($drawing, $other->id);

        // Someone placing the same shape fifty times in one design must not
        // be worth fifty points.
        for ($i = 0; $i < 50; $i++) {
            $again = $recorder->record($drawing, $other->id);
            $this->assertFalse($again['counted']);
            $this->assertSame('already-counted', $again['reason']);
        }

        $this->assertSame(1, $drawing->refresh()->use_count);
        $this->assertSame(
            DrawingModerationService::PUBLISH_BONUS + 1,
            CreatorPointEntry::balanceFor($author->id)
        );
    }

    public function test_using_your_own_drawing_earns_nothing(): void
    {
        $author = $this->user('author@test.com');
        $drawing = $this->publishedDrawing($author);

        $result = app(DrawingUsageRecorder::class)->record($drawing, $author->id);

        $this->assertFalse($result['counted']);
        $this->assertSame('own-drawing', $result['reason']);
        $this->assertSame(0, $drawing->refresh()->use_count);
        $this->assertSame(
            DrawingModerationService::PUBLISH_BONUS,
            CreatorPointEntry::balanceFor($author->id)
        );
    }

    public function test_an_unpublished_drawing_earns_nothing(): void
    {
        $author = $this->user('author@test.com');
        $other = $this->user('other@test.com');

        $private = Drawing::create([
            'user_id' => $author->id,
            'title' => 'Not shared',
            'document' => ['paths' => [], 'textItems' => [], 'width' => 10, 'height' => 10],
            'status' => Drawing::STATUS_PRIVATE,
        ]);

        $result = app(DrawingUsageRecorder::class)->record($private, $other->id);

        $this->assertFalse($result['counted']);
        $this->assertSame('not-reusable', $result['reason']);
        $this->assertSame(0, CreatorPointEntry::balanceFor($author->id));
    }

    public function test_the_publish_bonus_is_paid_only_once(): void
    {
        $author = $this->user('author@test.com');
        $admin = $this->user('admin@test.com');
        $drawing = $this->publishedDrawing($author);
        $moderation = app(DrawingModerationService::class);

        // Rejected, resubmitted and approved again must not pay a second bonus.
        $moderation->reject($drawing, $admin->id, 'Needs a change');
        $moderation->submit($drawing->refresh());
        $moderation->approve($drawing->refresh(), $admin->id);

        $this->assertSame(
            DrawingModerationService::PUBLISH_BONUS,
            CreatorPointEntry::balanceFor($author->id)
        );
    }

    public function test_the_balance_is_the_sum_of_the_ledger(): void
    {
        $author = $this->user('author@test.com');
        $drawing = $this->publishedDrawing($author);

        // A correction is a reversing entry, never an edit.
        CreatorPointEntry::create([
            'user_id' => $author->id,
            'amount' => -2,
            'reason' => CreatorPointEntry::REASON_REVERSAL,
            'drawing_id' => $drawing->id,
            'note' => 'Correcting a mistake',
        ]);

        $this->assertSame(
            DrawingModerationService::PUBLISH_BONUS - 2,
            CreatorPointEntry::balanceFor($author->id)
        );
    }
}
