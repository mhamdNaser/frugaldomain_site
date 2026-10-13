<?php

namespace App\Modules\Form\Jobs;

use App\Modules\Form\Models\Form;
use App\Modules\Form\Models\FormSubmission;
use App\Modules\Form\Support\FieldTypes;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Everything about a submission that does not have to happen while the visitor
 * waits.
 *
 * The request wrote the submission and claimed its unique answers. This job
 * does the rest: mirrors the searchable answers into the indexed table, moves
 * the owner's counters and sends the notification. A thousand people answering
 * in the same minute therefore produce a thousand quick inserts and a thousand
 * queued jobs that the worker drains at its own pace, instead of a thousand
 * requests each updating the same row of the forms table and queueing behind
 * one another for the lock.
 *
 * It is written to be safe to run twice. The queue guarantees *at least* once,
 * not exactly once - a worker killed mid-job will see the same payload again -
 * so the counter increment happens behind an atomic status claim, and the
 * indexed rows are replaced rather than appended.
 */
class ProcessFormSubmission implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** Spread the retries: a database that is busy now will still be busy in a second. */
    public array $backoff = [10, 60];

    public function __construct(public int $submissionId) {}

    public function handle(): void
    {
        $submission = FormSubmission::find($this->submissionId);

        if (! $submission) {
            return;
        }

        /*
         * Claim the row. The UPDATE only matches while the status is still
         * `received`, so of two workers holding the same payload exactly one
         * gets a non-zero result and the other returns without touching the
         * counters.
         */
        $claimed = DB::connection('forms')
            ->table('form_submissions')
            ->where('id', $submission->id)
            ->where('status', FormSubmission::STATUS_RECEIVED)
            ->update([
                'status' => FormSubmission::STATUS_INDEXED,
                'updated_at' => now(),
            ]);

        if (! $claimed) {
            return;
        }

        try {
            $form = Form::with('fields')->find($submission->form_id);

            if (! $form) {
                // The form was deleted while the job was waiting. The answers
                // stay - they are someone's data - but there is nothing left
                // to index them against.
                return;
            }

            $this->indexValues($form, $submission);
            $this->bumpCounters($form, $submission);
            $this->notifyOwner($form, $submission);
        } catch (\Throwable $e) {
            // Hand the row back so a retry can pick it up, then let the queue
            // see the failure. Without this the claim above would make a
            // failed attempt look processed and lose the count.
            DB::connection('forms')
                ->table('form_submissions')
                ->where('id', $submission->id)
                ->update(['status' => FormSubmission::STATUS_RECEIVED]);

            throw $e;
        }
    }

    /**
     * Mirror the answers into the indexed table.
     *
     * Unique fields are already there - the request wrote them, which is the
     * whole point - so they are skipped here rather than inserted twice.
     * Long free text is left in the JSON column: indexing a paragraph buys
     * nothing a filter would use.
     */
    private function indexValues(Form $form, FormSubmission $submission): void
    {
        $answers = $submission->data ?? [];
        $rows = [];
        $now = now();

        foreach ($form->fields as $field) {
            if ($field->unique || $field->type === 'textarea') {
                continue;
            }

            $value = $answers[$field->key] ?? null;

            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            $rows[] = array_merge(FieldTypes::indexValue($field->type, $value), [
                'form_id' => $form->id,
                'submission_id' => $submission->id,
                'field_key' => $field->key,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (! $rows) {
            return;
        }

        /*
         * Replace rather than append, so a second run of this job leaves the
         * same rows behind instead of duplicating every answer. The unique
         * rows are protected by the field_key filter: they must not be
         * deleted, because deleting them would release the uniqueness claim.
         */
        $keys = array_column($rows, 'field_key');

        DB::connection('forms')->transaction(function () use ($submission, $keys, $rows) {
            DB::connection('forms')
                ->table('form_submission_values')
                ->where('submission_id', $submission->id)
                ->whereIn('field_key', $keys)
                ->delete();

            foreach (array_chunk($rows, 100) as $chunk) {
                DB::connection('forms')->table('form_submission_values')->insert($chunk);
            }
        });
    }

    /**
     * Move the owner's counters.
     *
     * An atomic increment rather than a count: counting means scanning the
     * submissions of another database on every dashboard load, and reading
     * then writing would lose increments under load.
     */
    private function bumpCounters(Form $form, FormSubmission $submission): void
    {
        Form::whereKey($form->id)->update([
            'submissions_count' => DB::raw('submissions_count + 1'),
            'last_submission_at' => $submission->created_at ?? now(),
        ]);
    }

    /**
     * Tell the owner, when they asked to be told.
     *
     * A failure here must not fail the job: the answer is safely stored and
     * re-running the whole job to retry an email would re-send it. So it is
     * logged and left.
     */
    private function notifyOwner(Form $form, FormSubmission $submission): void
    {
        if (empty($form->settingsWithDefaults()['collect_email_notifications'])) {
            return;
        }

        $email = $form->user?->email;

        if (! $email) {
            return;
        }

        try {
            $title = $form->title_ar ?: $form->title;
            $url = rtrim((string) config('app.frontend_url', config('app.url')), '/') . '/account/forms/' . $form->id;

            Mail::raw(
                "A new response arrived on \"{$title}\".\n\nTotal responses: {$form->submissions_count}\n\n{$url}",
                fn ($message) => $message->to($email)->subject("New response: {$title}")
            );
        } catch (\Throwable $e) {
            Log::warning('Form notification failed', [
                'form_id' => $form->id,
                'submission_id' => $submission->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
