<?php

namespace App\Modules\Form\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Form\Models\Form;
use App\Modules\Form\Models\FormSubmission;
use App\Modules\Form\Support\SubmissionIntake;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * The shared link: what someone who was sent a form can do with it.
 *
 * Public, so everything it returns is deliberate. The schema carries the
 * questions and their rules and nothing about the owner - not their name, not
 * their id, not how many people have answered. A visitor opening a form they
 * were sent should learn the questions and no more than that.
 */
class PublicFormController extends Controller
{
    /**
     * The form, by slug or uuid.
     *
     * Both work on purpose: the slug makes a readable link, and the uuid keeps
     * an already-shared link alive after the owner renames the form.
     */
    public function show(Request $request, string $handle)
    {
        $form = $this->resolve($handle);

        // A draft is nobody's business but its owner's - unless the owner is
        // the one looking, which is how the builder previews it.
        if ($form->status === Form::STATUS_DRAFT && $request->user('sanctum')?->id !== $form->user_id) {
            abort(404);
        }

        $settings = $form->settingsWithDefaults();

        return response()->json([
            'data' => [
                'uuid' => $form->uuid,
                'slug' => $form->slug,
                'title' => $form->title,
                'title_ar' => $form->title_ar,
                'description' => $form->description,
                'description_ar' => $form->description_ar,
                'is_open' => $form->isAcceptingSubmissions(),
                'closed_reason' => $form->closedReason(),
                'require_login' => (bool) $settings['require_login'],
                'success_message' => $settings['success_message'],
                'success_message_ar' => $settings['success_message_ar'],
                'fields' => $form->fields->map(fn ($field) => $field->toPublicSchema())->values(),
                // Issued per load and sent back with the answers: it is what
                // tells a submission that skipped the page from one that did.
                'token' => SubmissionIntake::issueToken($form),
            ],
        ]);
    }

    public function submit(Request $request, string $handle)
    {
        $form = $this->resolve($handle);

        if (! $form->isAcceptingSubmissions()) {
            return response()->json([
                'message' => 'This form is no longer accepting responses.',
                'closed_reason' => $form->closedReason(),
            ], 422);
        }

        /*
         * The route is public, so the signed-in user is resolved explicitly
         * rather than by middleware: a token means we know who answered, no
         * token means an anonymous response, and both are allowed unless the
         * owner said otherwise.
         */
        $user = $request->user('sanctum');

        if ($user) {
            $request->setUserResolver(fn () => $user);
        }

        $settings = $form->settingsWithDefaults();

        if ($settings['require_login'] && ! $user) {
            return response()->json([
                'message' => 'Please sign in to answer this form.',
                'closed_reason' => 'login_required',
            ], 401);
        }

        SubmissionIntake::assertHuman($form, $request);

        if (empty($settings['allow_multiple_per_user'])) {
            $this->assertFirstAnswer($form, $request, $user?->id);
        }

        $answers = SubmissionIntake::validate($form, $request);
        $submission = SubmissionIntake::store($form, $request, $answers);

        return response()->json([
            'message' => $settings['success_message'] ?: 'Thank you, your response has been recorded.',
            'data' => ['uuid' => $submission->uuid],
        ], 201);
    }

    /**
     * "One response per person", as well as that can be known.
     *
     * For a signed-in person it is exact. For an anonymous one it is the
     * hashed address, which is a guess: a household behind one address shares
     * it, and a phone changes it by walking down the street. So it is a check
     * the owner opts into, and a form that truly needs one answer per person
     * should also require signing in - which is why both settings exist.
     */
    private function assertFirstAnswer(Form $form, Request $request, ?int $userId): void
    {
        $query = FormSubmission::where('form_id', $form->id);

        if ($userId) {
            $query->where('submitted_by', $userId);
        } else {
            $hash = FormSubmission::hashIp($request->ip());

            if (! $hash) {
                return;
            }

            $query->whereNull('submitted_by')->where('ip_hash', $hash);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'answers' => 'You have already answered this form.',
            ]);
        }
    }

    private function resolve(string $handle): Form
    {
        return Form::with('fields')
            ->where(fn ($query) => $query->where('slug', $handle)->orWhere('uuid', $handle))
            ->firstOrFail();
    }
}
