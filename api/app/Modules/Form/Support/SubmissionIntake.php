<?php

namespace App\Modules\Form\Support;

use App\Modules\Form\Jobs\ProcessFormSubmission;
use App\Modules\Form\Models\Form;
use App\Modules\Form\Models\FormSubmission;
use App\Modules\Form\Models\FormSubmissionValue;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Accepting an answer, in as little work as the request can get away with.
 *
 * The shape of this class is the whole performance story. A form can go out to
 * a few thousand people at once and they all arrive in the same few minutes,
 * so the request does only what cannot be deferred:
 *
 *   1. validate the answers
 *   2. one INSERT for the submission
 *   3. one INSERT per *unique* field, inside the same transaction
 *   4. queue a job for everything else
 *
 * Step 3 is the part that cannot wait. A unique field is only unique if the
 * database says so at the moment of writing: two people submitting the same
 * national id in the same instant would both pass a SELECT, and a queue that
 * noticed afterwards could only apologise. So the unique answers go in now,
 * against a real unique index, and the submission rolls back with them.
 *
 * Everything that *can* wait does: the searchable (non-unique) values, the
 * owner's counters, the notification. That is the queued half, and it is why
 * a burst of submissions does not turn into a burst of writes to the forms
 * table.
 */
class SubmissionIntake
{
    /** How long a visitor must plausibly have spent on the page. */
    private const MIN_FILL_SECONDS = 2;

    /** How long a page may sit open before its token is stale. */
    private const MAX_FORM_AGE_SECONDS = 86400;

    /**
     * A token the public page is handed when it loads and must send back.
     *
     * It is encrypted rather than signed-and-readable so the timestamp cannot
     * be edited, which is what makes the "filled in impossibly fast" check
     * mean anything. A bot that skips loading the page has no token at all.
     */
    public static function issueToken(Form $form): string
    {
        return encrypt([
            'form' => $form->id,
            'at' => now()->timestamp,
            'n' => Str::random(8),
        ]);
    }

    /**
     * Cheap rejections, before any validation work.
     *
     * Honeypot and timing catch the overwhelming majority of automated
     * submissions at near-zero cost, which matters precisely because the
     * expensive path below is the one worth protecting.
     */
    public static function assertHuman(Form $form, Request $request): void
    {
        // A field no human sees, so anything in it was not typed by one.
        if (filled($request->input('_trap'))) {
            throw ValidationException::withMessages(['_trap' => 'Rejected.']);
        }

        $token = $request->input('_form_token');
        $reloadMessage = 'This form was not loaded properly. Please reload the page.';

        if (! is_string($token) || $token === '') {
            throw ValidationException::withMessages(['_form_token' => $reloadMessage]);
        }

        try {
            $payload = decrypt($token);
        } catch (\Throwable) {
            throw ValidationException::withMessages(['_form_token' => $reloadMessage]);
        }

        if ((int) ($payload['form'] ?? 0) !== (int) $form->id) {
            throw ValidationException::withMessages(['_form_token' => $reloadMessage]);
        }

        $age = now()->timestamp - (int) ($payload['at'] ?? 0);

        if ($age < self::MIN_FILL_SECONDS) {
            throw ValidationException::withMessages([
                '_trap' => 'That was too quick. Please try again.',
            ]);
        }

        if ($age > self::MAX_FORM_AGE_SECONDS) {
            throw ValidationException::withMessages([
                '_form_token' => 'This page has been open too long. Please reload it.',
            ]);
        }
    }

    /**
     * Validate the answers against the form's own fields.
     *
     * Rules are built from the stored field definitions every time rather than
     * trusted from the payload: the owner may have tightened a rule since the
     * visitor loaded the page, and the server's copy is the one that counts.
     */
    public static function validate(Form $form, Request $request): array
    {
        $fields = $form->fields;
        $arabic = str_starts_with(strtolower((string) $request->header('X-Language', 'en')), 'ar');

        $rules = [];
        $attributes = [];

        foreach ($fields as $field) {
            $rules['answers.' . $field->key] = $field->validationRules();
            // The question as the visitor saw it. A person reading "الرقم
            // الوطني" on the page should not be told that "national_id" is
            // wrong.
            $attributes['answers.' . $field->key] = $arabic
                ? ($field->label_ar ?: $field->label)
                : $field->label;

            if ($itemRules = $field->itemValidationRules()) {
                $rules['answers.' . $field->key . '.*'] = $itemRules;
            }
        }

        /*
         * Normalise before validating: Arabic-Indic digits, stray spaces and
         * the invisible marks a right-to-left keyboard inserts would otherwise
         * fail rules the answer actually satisfies.
         */
        $answers = (array) $request->input('answers', []);
        $clean = [];

        foreach ($fields as $field) {
            if (array_key_exists($field->key, $answers)) {
                $clean[$field->key] = FieldTypes::normalise($field->type, $answers[$field->key]);
            }
        }

        validator(['answers' => $clean], $rules, $arabic ? self::arabicMessages($fields) : [], $attributes)->validate();

        // Only the form's own keys survive, so an extra key in the payload
        // cannot smuggle itself into storage.
        return $clean;
    }

    /**
     * Arabic for the rules a form's answers are judged by.
     *
     * The project has no `lang/ar`, so Laravel's own messages are English
     * whatever the page is in - which would mean an Arabic form telling an
     * Arabic speaker "The الرقم الوطني field is required." The messages are
     * therefore supplied per field, which also lets each one be phrased for
     * the type it belongs to: a length in letters for text, in digits for an
     * identifier, in choices for a checkbox list.
     */
    private static function arabicMessages($fields): array
    {
        $messages = [];

        foreach ($fields as $field) {
            $key = 'answers.' . $field->key;
            $type = $field->type;

            /*
             * Worded to sidestep grammatical gender. A label can be masculine
             * or feminine - "الاسم" and "المحافظة" - and nothing in the field
             * says which, so ":attribute مطلوب" is wrong half the time. Asking
             * ("يرجى إدخال") and speaking of the value ("قيمة :attribute",
             * always feminine) agree with any label.
             */
            $isChoice = in_array($type, ['select', 'radio', 'checkboxes', 'boolean'], true);

            $messages[$key . '.required'] = $isChoice
                ? 'يرجى اختيار :attribute.'
                : 'يرجى إدخال :attribute.';

            $messages[$key . '.string'] = 'يجب أن تكون قيمة :attribute نصاً.';
            $messages[$key . '.email'] = 'يجب أن تكون قيمة :attribute بريداً إلكترونياً صحيحاً.';
            $messages[$key . '.url'] = 'يجب أن تكون قيمة :attribute رابطاً صحيحاً.';
            $messages[$key . '.numeric'] = 'يجب أن تكون قيمة :attribute رقماً.';
            $messages[$key . '.integer'] = 'يجب أن تكون قيمة :attribute رقماً صحيحاً بلا فواصل.';
            $messages[$key . '.boolean'] = 'يجب أن تكون قيمة :attribute نعم أو لا.';
            $messages[$key . '.array'] = 'يجب أن تكون قيمة :attribute اختياراً من القائمة.';
            $messages[$key . '.in'] = 'القيمة المختارة في :attribute غير موجودة في القائمة.';
            $messages[$key . '.date'] = 'يجب أن تكون قيمة :attribute تاريخاً صحيحاً.';
            $messages[$key . '.date_format'] = 'يجب أن تكون قيمة :attribute وقتاً بالصيغة :format.';
            $messages[$key . '.after_or_equal'] = 'يجب أن تكون قيمة :attribute في :date أو بعده.';
            $messages[$key . '.before_or_equal'] = 'يجب أن تكون قيمة :attribute في :date أو قبله.';
            $messages[$key . '.digits'] = 'يجب أن تكون قيمة :attribute :digits خانة بالأرقام.';

            // `regex` means something different for each type that uses one,
            // so the message says what was actually expected.
            $messages[$key . '.regex'] = match ($type) {
                'letters' => 'يجب أن تحتوي قيمة :attribute حروفاً فقط، بلا أرقام أو رموز.',
                'phone' => 'يجب أن تكون قيمة :attribute رقم هاتف صحيحاً.',
                'digits' => 'يجب أن تكون قيمة :attribute أرقاماً فقط.',
                default => 'صيغة :attribute غير صحيحة.',
            };

            /*
             * `min` and `max` are size rules: the same rule name counts
             * letters, a numeric value or a number of choices depending on
             * the field. Laravel picks its wording from the value it is
             * given, so the wording is chosen here per field instead.
             */
            [$minText, $maxText] = match ($type) {
                'number' => [
                    'يجب ألا تقل قيمة :attribute عن :min.',
                    'يجب ألا تزيد قيمة :attribute عن :max.',
                ],
                'checkboxes' => [
                    'يجب اختيار :min على الأقل في :attribute.',
                    'لا يمكن اختيار أكثر من :max في :attribute.',
                ],
                default => [
                    'يجب ألا تقل قيمة :attribute عن :min حرفاً.',
                    'يجب ألا تزيد قيمة :attribute عن :max حرفاً.',
                ],
            };

            $messages[$key . '.min'] = $minText;
            $messages[$key . '.max'] = $maxText;

            if ($field->itemValidationRules()) {
                $messages[$key . '.*.in'] = 'أحد الخيارات المحددة في :attribute غير موجود في القائمة.';
            }
        }

        return $messages;
    }

    /**
     * Write the submission.
     *
     * Throws a ValidationException naming the offending field when a unique
     * answer has already been given - the one error this path has to produce
     * synchronously, because it is the one the person needs to see before
     * they walk away from the page.
     */
    public static function store(Form $form, Request $request, array $answers): FormSubmission
    {
        $uniqueFields = $form->fields->where('unique', true);

        $submission = new FormSubmission([
            'uuid' => (string) Str::uuid(),
            'form_id' => $form->id,
            'form_user_id' => $form->user_id,
            'submitted_by' => $request->user()?->id,
            'data' => $answers,
            'status' => FormSubmission::STATUS_RECEIVED,
            'ip_hash' => FormSubmission::hashIp($request->ip()),
            'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
            'locale' => Str::limit((string) $request->header('X-Language', 'en'), 8, ''),
        ]);

        $duplicateField = null;

        try {
            DB::connection('forms')->transaction(function () use ($submission, $uniqueFields, $answers, $form, &$duplicateField) {
                $submission->save();

                foreach ($uniqueFields as $field) {
                    $value = $answers[$field->key] ?? null;

                    // An empty optional answer is not a duplicate of another
                    // empty one, so it is simply not claimed.
                    if ($value === null || $value === '' || $value === []) {
                        continue;
                    }

                    $row = FieldTypes::indexValue($field->type, $value);
                    $row['value_hash'] = FieldTypes::uniquenessHash($value);

                    try {
                        FormSubmissionValue::create(array_merge($row, [
                            'form_id' => $form->id,
                            'submission_id' => $submission->id,
                            'field_key' => $field->key,
                        ]));
                    } catch (QueryException $e) {
                        // Remembered, then rethrown, so the transaction
                        // unwinds and no half-written submission survives.
                        if (self::isDuplicate($e)) {
                            $duplicateField = $field;
                        }

                        throw $e;
                    }
                }
            });
        } catch (QueryException $e) {
            if ($duplicateField) {
                throw ValidationException::withMessages([
                    'answers.' . $duplicateField->key => 'This value has already been submitted.',
                ]);
            }

            throw $e;
        }

        /*
         * The rest is queued. Counting, indexing the searchable fields and
         * notifying the owner can all happen a second later without anyone
         * noticing - and keeping them out of the request is what lets the
         * site absorb a thousand submissions in a minute.
         */
        ProcessFormSubmission::dispatch($submission->id);

        return $submission;
    }

    /** MySQL 1062, Postgres 23505, SQLite "UNIQUE constraint failed". */
    private static function isDuplicate(QueryException $e): bool
    {
        $code = (string) ($e->errorInfo[1] ?? '');
        $message = strtolower($e->getMessage());

        return $code === '1062'
            || (string) $e->getCode() === '23505'
            || str_contains($message, 'unique constraint failed')
            || str_contains($message, 'duplicate entry');
    }
}
