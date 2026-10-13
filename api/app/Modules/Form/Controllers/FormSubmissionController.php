<?php

namespace App\Modules\Form\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Form\Models\Form;
use App\Modules\Form\Models\FormSubmission;
use App\Modules\Form\Resources\FormSubmissionResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * What came back: the owner's view of the responses to their own form.
 *
 * Reads here cross into the answers database, so they are kept narrow - a page
 * at a time, and the filters run against the indexed values table rather than
 * against the JSON, which is exactly what that table is for.
 */
class FormSubmissionController extends Controller
{
    public function index(Request $request, int $formId)
    {
        $form = $this->owned($request, $formId);

        $query = FormSubmission::where('form_id', $form->id)
            ->whereIn('status', [FormSubmission::STATUS_RECEIVED, FormSubmission::STATUS_INDEXED])
            ->latest('id');

        /*
         * A search term is resolved through the values table and then applied
         * as an id filter. Matching inside the JSON column would mean reading
         * every response of the form on every keystroke.
         */
        if ($request->filled('q')) {
            $ids = DB::connection('forms')
                ->table('form_submission_values')
                ->where('form_id', $form->id)
                ->where('value_text', 'like', '%' . $request->input('q') . '%')
                ->limit(5000)
                ->pluck('submission_id');

            $query->whereIn('id', $ids);
        }

        if ($request->filled('field') && $request->filled('value')) {
            $ids = DB::connection('forms')
                ->table('form_submission_values')
                ->where('form_id', $form->id)
                ->where('field_key', $request->input('field'))
                ->where('value_text', $request->input('value'))
                ->limit(5000)
                ->pluck('submission_id');

            $query->whereIn('id', $ids);
        }

        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->date('from'));
        }

        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->date('to'));
        }

        $submissions = $query->paginate(min(max((int) $request->input('per_page', 25), 1), 100));

        return FormSubmissionResource::collection($submissions)->additional([
            'meta' => [
                'form' => [
                    'id' => $form->id,
                    'title' => $form->title,
                    'title_ar' => $form->title_ar,
                    'submissions_count' => (int) $form->submissions_count,
                ],
                // The column order of the export and the table, from the
                // definition rather than from whatever the first response
                // happened to contain.
                'columns' => $form->fields->map(fn ($field) => [
                    'key' => $field->key,
                    'label' => $field->label,
                    'label_ar' => $field->label_ar,
                    'type' => $field->type,
                ])->values(),
            ],
        ]);
    }

    public function destroy(Request $request, int $formId, int $submissionId)
    {
        $form = $this->owned($request, $formId);

        $submission = FormSubmission::where('form_id', $form->id)->whereKey($submissionId)->firstOrFail();

        // The indexed values go with it, which also releases any uniqueness
        // claim it held - otherwise deleting a response would leave the
        // person who sent it unable to send another.
        $submission->values()->delete();
        $submission->delete();

        Form::whereKey($form->id)->where('submissions_count', '>', 0)->decrement('submissions_count');

        return response()->json(['message' => 'Response deleted.']);
    }

    /**
     * Every answer at once, for a spreadsheet built in the browser.
     *
     * The owner wants a real .xlsx whose columns match the sheet they already
     * work with - typed cells, a right-to-left view, dates Excel recognises.
     * Writing that server-side would mean a spreadsheet library and the zip
     * extension on the host; the site already ships SheetJS for the dashboard
     * tables, so the rows are handed over as JSON and the file is assembled
     * where that library already lives.
     *
     * Read in chunks and capped: this is the one endpoint that returns a whole
     * form at once, and a form with a hundred thousand answers must not be
     * allowed to exhaust the worker's memory. Past the cap the client is told
     * to use the streamed CSV instead, which has no such limit.
     */
    public const EXPORT_ROW_CAP = 20000;

    public function rows(Request $request, int $formId)
    {
        $form = $this->owned($request, $formId);
        $fields = $form->fields;

        $rows = [];
        $truncated = false;

        FormSubmission::where('form_id', $form->id)
            ->whereIn('status', [FormSubmission::STATUS_RECEIVED, FormSubmission::STATUS_INDEXED])
            ->orderBy('id')
            ->chunk(500, function ($chunk) use (&$rows, &$truncated, $fields) {
                foreach ($chunk as $submission) {
                    if (count($rows) >= self::EXPORT_ROW_CAP) {
                        $truncated = true;

                        return false;
                    }

                    $answers = $submission->data ?? [];
                    $row = [];

                    // Keyed by field, in the form's order, so the client does
                    // not have to know anything about the storage shape.
                    foreach ($fields as $field) {
                        $row[$field->key] = $answers[$field->key] ?? null;
                    }

                    $rows[] = [
                        'id' => $submission->id,
                        'created_at' => (string) $submission->created_at,
                        'answers' => $row,
                    ];
                }

                return true;
            });

        return response()->json([
            'form' => [
                'id' => $form->id,
                'title' => $form->title,
                'title_ar' => $form->title_ar,
                'serial_label' => $form->settingsWithDefaults()['serial_label'],
            ],
            'columns' => $fields->map(fn ($field) => [
                'key' => $field->key,
                'label' => $field->label,
                'label_ar' => $field->label_ar,
                'type' => $field->type,
            ])->values(),
            'rows' => $rows,
            'truncated' => $truncated,
            'cap' => self::EXPORT_ROW_CAP,
        ]);
    }

    /**
     * CSV, streamed and chunked.
     *
     * A form with ten thousand responses must not be assembled in memory, so
     * rows are written out as they are read. The BOM is there because Excel
     * reads a UTF-8 file without one as Latin-1 and turns every Arabic answer
     * into mojibake.
     */
    public function export(Request $request, int $formId): StreamedResponse
    {
        $form = $this->owned($request, $formId);
        $fields = $form->fields;

        $filename = 'form-' . $form->slug . '-' . now()->format('Y-m-d') . '.csv';

        return response()->stream(function () use ($form, $fields) {
            $out = fopen('php://output', 'w');

            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, array_merge(
                ['#', 'submitted_at'],
                $fields->map(fn ($field) => $field->label_ar ?: $field->label)->all()
            ));

            FormSubmission::where('form_id', $form->id)
                ->whereIn('status', [FormSubmission::STATUS_RECEIVED, FormSubmission::STATUS_INDEXED])
                ->orderBy('id')
                ->chunk(500, function ($rows) use ($out, $fields) {
                    foreach ($rows as $row) {
                        $answers = $row->data ?? [];

                        fputcsv($out, array_merge(
                            [$row->id, (string) $row->created_at],
                            $fields->map(function ($field) use ($answers) {
                                $value = $answers[$field->key] ?? '';

                                return self::csvCell(is_array($value) ? implode(' | ', $value) : (string) $value);
                            })->all()
                        ));
                    }

                    flush();
                });

            fclose($out);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * An answer is text, not a formula.
     *
     * A spreadsheet treats a cell starting with = + - or @ as an expression,
     * so an answer of `=HYPERLINK(...)` would run when the owner opens the
     * export. Prefixing an apostrophe is what makes the file inert; the
     * apostrophe itself is not shown by Excel or LibreOffice.
     */
    private static function csvCell(string $value): string
    {
        return $value !== '' && in_array($value[0], ['=', '+', '-', '@', chr(9), chr(13)], true)
            ? "'" . $value
            : $value;
    }

    private function owned(Request $request, int $id): Form
    {
        return Form::ownedBy($request->user()->id)->with('fields')->whereKey($id)->firstOrFail();
    }
}
