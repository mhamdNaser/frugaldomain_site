<?php

namespace Tests\Feature;

use App\Modules\Learning\database\seeders\LearningCurriculumSeeder;
use App\Modules\Learning\Models\LearningCourse;
use App\Modules\Learning\Models\LearningLesson;
use App\Modules\Learning\Services\CourseTransfer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Mail;
use App\Modules\Account\Mail\EmailVerificationCode;
use App\Modules\User\Models\User;
use Tests\TestCase;

class LearningApiTest extends TestCase
{
    use RefreshDatabase;

    /** Lesson content is for signed-in members who confirmed their email. */
    private function signIn(bool $verified = true): User
    {
        $user = User::unguarded(fn () => User::create([
            'name' => 'Member', 'first_name' => 'Member', 'email' => 'member@example.com', 'password' => bcrypt('secret-password'),
            'email_verified_at' => $verified ? now() : null,
        ]));
        Sanctum::actingAs($user);

        return $user;
    }

    private function document(array $lessonOverrides = []): array
    {
        return [
            'format' => 'frugal-course@1',
            'category' => ['slug' => 'cybersecurity', 'name' => 'Cybersecurity', 'color' => '#0891b2'],
            'course' => ['slug' => 'demo', 'title' => 'Demo course', 'level' => 'beginner', 'language' => 'en', 'status' => 'published'],
            'stages' => [[
                'slug' => 'stage-one', 'title' => 'Stage one', 'code' => '100-160',
                'units' => [[
                    'slug' => 'unit-one', 'title' => 'Unit one',
                    'lessons' => [
                        array_merge([
                            'slug' => 'first', 'title' => 'First lesson', 'status' => 'published',
                            'blocks' => [
                                ['type' => 'text', 'markdown' => '## Hello'],
                                ['type' => 'animation', 'kind' => 'flow', 'data' => [
                                    'nodes' => [['id' => 'a', 'label' => 'A']],
                                    'steps' => [['at' => 'a', 'caption' => 'Here']],
                                ], 'unknown_field' => 'dropped'],
                            ],
                        ], $lessonOverrides),
                        ['slug' => 'second', 'title' => 'Second lesson', 'status' => 'draft'],
                        ['slug' => 'third', 'title' => 'Third lesson', 'status' => 'published', 'blocks' => [['type' => 'divider']]],
                    ],
                ]],
            ]],
        ];
    }

    public function test_import_builds_the_tree_and_the_public_api_serves_it(): void
    {
        app(CourseTransfer::class)->import($this->document());

        $this->getJson('/api/learn/categories')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'cybersecurity')
            ->assertJsonPath('data.0.courses_count', 1);

        $this->getJson('/api/learn/courses?category=cybersecurity')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'demo')
            ->assertJsonPath('data.0.lessons_count', 3)
            ->assertJsonPath('data.0.published_lessons_count', 2);

        $this->getJson('/api/learn/courses/demo')
            ->assertOk()
            ->assertJsonPath('data.stages.0.code', '100-160')
            ->assertJsonPath('data.stages.0.units.0.lessons.1.available', false);
    }

    public function test_visitors_see_a_lesson_preview_but_not_its_content(): void
    {
        app(CourseTransfer::class)->import($this->document(['summary' => 'What it teaches', 'objectives' => ['Explain things']]));

        // Courses and the syllabus stay public.
        $this->getJson('/api/learn/courses/demo')->assertOk();

        $this->getJson('/api/learn/courses/demo/lessons/first')
            ->assertOk()
            ->assertJsonPath('data.locked', true)
            ->assertJsonPath('data.title', 'First lesson')
            ->assertJsonPath('data.summary', 'What it teaches')
            ->assertJsonPath('data.objectives.0', 'Explain things')
            ->assertJsonPath('data.blocks', []);

        $this->signIn();
        $this->getJson('/api/learn/courses/demo/lessons/first')
            ->assertOk()
            ->assertJsonPath('data.locked', false)
            ->assertJsonCount(2, 'data.blocks');
    }

    public function test_an_unconfirmed_email_keeps_the_lesson_locked_until_the_code_is_entered(): void
    {
        Mail::fake();
        app(CourseTransfer::class)->import($this->document());
        $this->signIn(verified: false);

        $this->getJson('/api/learn/courses/demo/lessons/first')
            ->assertJsonPath('data.locked', true)
            ->assertJsonPath('data.lock_reason', 'verify_email')
            ->assertJsonPath('data.blocks', []);

        $this->postJson('/api/account/email/send-code')->assertOk();
        $code = null;
        Mail::assertSent(EmailVerificationCode::class, function ($mail) use (&$code) {
            $code = $mail->code;

            return true;
        });

        $wrong = $code === '000000' ? '111111' : '000000';
        $this->postJson('/api/account/email/verify', ['code' => $wrong])->assertStatus(422);
        $this->postJson('/api/account/email/verify', ['code' => $code])->assertOk()->assertJsonPath('email_verified', true);

        $this->getJson('/api/learn/courses/demo/lessons/first')
            ->assertJsonPath('data.locked', false)
            ->assertJsonCount(2, 'data.blocks');
    }

    public function test_members_progress_is_kept_per_course(): void
    {
        app(CourseTransfer::class)->import($this->document());
        $this->getJson('/api/learn/courses/demo/progress')->assertUnauthorized();
        $this->signIn();

        // A draft or unknown slug is ignored; marking twice is harmless.
        $this->postJson('/api/learn/courses/demo/progress', ['lessons' => ['first', 'third', 'second', 'nope'], 'done' => true])
            ->assertOk()->assertJsonCount(2, 'data.completed');
        $this->postJson('/api/learn/courses/demo/progress', ['lessons' => ['first'], 'done' => true])->assertOk();
        $this->getJson('/api/learn/courses/demo/lessons/first')->assertJsonPath('data.completed', true);

        $this->postJson('/api/learn/courses/demo/progress', ['lessons' => ['first'], 'done' => false])
            ->assertOk()->assertJsonPath('data.completed', ['third']);
    }

    public function test_lessons_link_to_published_neighbours_and_drafts_are_hidden(): void
    {
        app(CourseTransfer::class)->import($this->document());
        $this->signIn();

        $lesson = $this->getJson('/api/learn/courses/demo/lessons/first')
            ->assertOk()
            ->assertJsonPath('data.previous', null)
            ->assertJsonPath('data.next.slug', 'third')
            ->assertJsonPath('data.total', 2)
            ->json('data');

        $this->assertArrayNotHasKey('unknown_field', $lesson['blocks'][1]);
        $this->getJson('/api/learn/courses/demo/lessons/second')->assertNotFound();
    }

    public function test_draft_courses_are_not_public(): void
    {
        $document = $this->document();
        $document['course']['status'] = 'draft';
        app(CourseTransfer::class)->import($document);

        $this->getJson('/api/learn/courses')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/learn/courses/demo')->assertNotFound();
    }

    public function test_invalid_blocks_are_rejected(): void
    {
        $this->expectException(ValidationException::class);

        app(CourseTransfer::class)->import($this->document([
            'blocks' => [['type' => 'quiz', 'questions' => [['question' => 'Q?', 'options' => ['only one']]]]],
        ]));
    }

    public function test_fill_mode_never_overwrites_edited_lessons(): void
    {
        $transfer = app(CourseTransfer::class);
        $transfer->import($this->document());

        LearningLesson::where('slug', 'first')->first()->update([
            'blocks' => [['id' => 'x', 'type' => 'text', 'markdown' => 'Edited in the dashboard']],
        ]);

        $transfer->import($this->document(), 'fill');

        $this->assertSame('Edited in the dashboard', LearningLesson::where('slug', 'first')->first()->blocks[0]['markdown']);
        $this->assertSame(1, LearningCourse::count());
    }

    public function test_fill_mode_replaces_a_review_pointer_with_a_written_lesson(): void
    {
        $transfer = app(CourseTransfer::class);
        $transfer->import($this->document([
            'blocks' => [['id' => 'review', 'type' => 'callout', 'variant' => 'info', 'markdown' => 'See the other lesson.']],
        ]));

        $transfer->import($this->document(), 'fill');

        $this->assertSame('## Hello', LearningLesson::where('slug', 'first')->first()->blocks[0]['markdown']);
    }

    public function test_export_round_trips(): void
    {
        $transfer = app(CourseTransfer::class);
        $course = $transfer->import($this->document());
        $exported = $transfer->export($course);

        $this->assertSame('frugal-course@1', $exported['format']);
        $this->assertCount(3, $exported['stages'][0]['units'][0]['lessons']);

        $transfer->import($exported, 'replace');
        $this->assertSame(3, LearningLesson::count());
    }

    public function test_arabic_is_served_when_asked_for_and_falls_back_to_english(): void
    {
        $document = $this->document([
            'title_ar' => 'الدرس الأول',
            'summary_ar' => 'ملخص',
            'blocks_ar' => [['id' => 'a', 'type' => 'text', 'markdown' => '## مرحبا']],
        ]);
        $document['course']['title_ar'] = 'دورة تجريبية';
        app(CourseTransfer::class)->import($document);
        $this->signIn();

        $this->getJson('/api/learn/courses/demo/lessons/first', ['X-Language' => 'ar'])
            ->assertOk()
            ->assertJsonPath('data.title', 'الدرس الأول')
            ->assertJsonPath('data.content_language', 'ar')
            ->assertJsonPath('data.blocks.0.markdown', '## مرحبا')
            ->assertJsonPath('data.course.title', 'دورة تجريبية')
            ->assertJsonPath('data.next.title', 'Third lesson');

        // No Arabic content: the English lesson is served, and said to be.
        $this->getJson('/api/learn/courses/demo/lessons/third', ['X-Language' => 'ar'])
            ->assertOk()
            ->assertJsonPath('data.content_language', 'en')
            ->assertJsonPath('data.title', 'Third lesson');

        $this->getJson('/api/learn/courses/demo/lessons/first')
            ->assertJsonPath('data.title', 'First lesson')
            ->assertJsonPath('data.content_language', 'en');

        $this->getJson('/api/learn/courses/demo', ['X-Language' => 'ar'])
            ->assertJsonPath('data.stages.0.units.0.lessons.0.has_ar', true)
            ->assertJsonPath('data.stages.0.units.0.lessons.2.has_ar', false);
    }

    public function test_fill_mode_adds_arabic_to_existing_lessons(): void
    {
        $transfer = app(CourseTransfer::class);
        $transfer->import($this->document());

        $transfer->import($this->document([
            'title_ar' => 'الدرس الأول',
            'blocks_ar' => [['id' => 'a', 'type' => 'text', 'markdown' => 'نص']],
        ]), 'fill');

        $lesson = LearningLesson::where('slug', 'first')->first();
        $this->assertSame('الدرس الأول', $lesson->title_ar);
        $this->assertSame('نص', $lesson->blocks_ar[0]['markdown']);
    }

    public function test_admin_routes_require_authentication(): void
    {
        $this->getJson('/api/admin/learn/courses')->assertUnauthorized();
        $this->postJson('/api/admin/learn/import', [])->assertUnauthorized();
    }

    public function test_the_shipped_curriculum_seeds_and_is_idempotent(): void
    {
        $this->seed(LearningCurriculumSeeder::class);
        $courses = LearningCourse::count();
        $lessons = LearningLesson::count();

        $this->assertSame(10, $courses);
        $this->assertSame(655, $lessons);
        $this->assertGreaterThan(0, LearningLesson::where('status', 'published')->count());

        $this->seed(LearningCurriculumSeeder::class);
        $this->assertSame($lessons, LearningLesson::count());
    }

    public function test_the_sitemap_lists_written_lessons_in_both_languages_only(): void
    {
        $this->seed(LearningCurriculumSeeder::class);

        $response = $this->get('/api/learn/sitemap.xml');
        $response->assertOk();
        $this->assertStringStartsWith('application/xml', $response->headers->get('Content-Type'));
        $xml = $response->getContent();

        $site = 'https://frugaldomain.site';
        // A course, and a written lesson with its Arabic twin and hreflang pair.
        $this->assertStringContainsString("<loc>{$site}/learn/networking-foundations-ccna</loc>", $xml);
        $this->assertStringContainsString("<loc>{$site}/learn/networking-foundations-ccna/tcp-udp-icmp-and-arp</loc>", $xml);
        $this->assertStringContainsString("<loc>{$site}/ar/learn/networking-foundations-ccna/tcp-udp-icmp-and-arp</loc>", $xml);
        $this->assertStringContainsString('hreflang="x-default"', $xml);
        // A review pointer (published, but thin) and an unwritten lesson are left out.
        $this->assertStringNotContainsString('/learn/networking-foundations-ccna/networking-fundamentals<', $xml);
        $draft = LearningLesson::where('status', 'draft')->firstOrFail();
        $this->assertStringNotContainsString("/{$draft->slug}<", $xml);

        $this->assertNotFalse(simplexml_load_string($xml));
    }
}
