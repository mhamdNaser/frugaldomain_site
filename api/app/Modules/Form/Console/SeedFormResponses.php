<?php

namespace App\Modules\Form\Console;

use App\Modules\Form\Jobs\ProcessFormSubmission;
use App\Modules\Form\Models\Form;
use App\Modules\Form\Models\FormSubmission;
use App\Modules\Form\Support\FormFiles;
use App\Modules\Form\Support\SubmissionIntake;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Plausible answers for a form, so its owner can see the table, the filters
 * and the export before sending the link to anybody.
 *
 * The answers are generated from each field's own definition rather than from
 * a fixed list, so this works for any form: a thirteen-digit field gets
 * thirteen digits, a dropdown gets one of its own options, a unique field gets
 * a value nobody else has. That also means the sample exercises the real
 * rules - if a rule is wrong, this is where it shows.
 *
 * They go in through the same path a real submission takes, so the counters,
 * the indexed values and the uniqueness index all end up in the state they
 * would really be in. Every sample is marked, and `--clean` removes exactly
 * those and nothing else.
 */
class SeedFormResponses extends Command
{
    /** What marks a row as generated rather than sent by a person. */
    public const MARKER = 'sample-data';

    protected $signature = 'form:sample
        {form : The form id or slug}
        {count=8 : How many responses to add}
        {--clean : Remove previously generated samples instead of adding more}
        {--with-images : Attach a generated picture to every image field}';

    protected $description = 'Add sample responses to a form, or remove the ones added before';

    public function handle(): int
    {
        $form = Form::with('fields')
            ->where('id', (int) $this->argument('form'))
            ->orWhere('slug', $this->argument('form'))
            ->first();

        if (! $form) {
            $this->error('No form matches "' . $this->argument('form') . '".');

            return self::FAILURE;
        }

        return $this->option('clean') ? $this->clean($form) : $this->seed($form);
    }

    private function seed(Form $form): int
    {
        $count = max(1, min((int) $this->argument('count'), 200));

        $this->info("Adding {$count} sample responses to \"{$form->title_ar}\" (#{$form->id}).");

        $added = 0;

        for ($i = 1; $i <= $count; $i++) {
            $answers = [];
            $files = [];

            foreach ($form->fields as $field) {
                $value = $this->valueFor($field, $i);

                if ($value instanceof UploadedFile) {
                    $files[$field->key] = $value;
                } elseif ($value !== null) {
                    $answers[$field->key] = $value;
                }
            }

            $request = Request::create('/api/f/' . $form->slug, 'POST', ['answers' => $answers], [], ['answers' => $files]);
            $request->headers->set('X-Language', 'ar');
            // Marked, and from an address that is reserved for documentation,
            // so a sample can never be mistaken for a real response.
            $request->headers->set('User-Agent', self::MARKER);
            $request->server->set('REMOTE_ADDR', '203.0.113.' . (($i % 250) + 1));

            try {
                $clean = SubmissionIntake::validate($form, $request);
                $submission = SubmissionIntake::store($form, $request, $clean);

                // Run the indexing now rather than leaving it for the worker,
                // so the table and the filters are complete the moment this
                // command returns.
                (new ProcessFormSubmission($submission->id))->handle();

                $added++;
            } catch (\Throwable $e) {
                $this->warn("  response {$i} was refused: " . Str::limit($e->getMessage(), 160));
            }
        }

        $this->info("Added {$added} of {$count}.");
        $this->line('Remove them later with:  php artisan form:sample ' . $form->id . ' --clean');

        return self::SUCCESS;
    }

    private function clean(Form $form): int
    {
        $samples = FormSubmission::where('form_id', $form->id)
            ->where('user_agent', self::MARKER)
            ->get();

        if ($samples->isEmpty()) {
            $this->info('There are no generated samples on this form.');

            return self::SUCCESS;
        }

        foreach ($samples as $submission) {
            $submission->values()->delete();
            FormFiles::forgetSubmission($form, $submission->uuid);
            $submission->delete();
        }

        // The counter only loses what was actually removed, so a form that
        // also holds real responses keeps counting them.
        Form::whereKey($form->id)->update([
            'submissions_count' => max(0, (int) $form->submissions_count - $samples->count()),
        ]);

        $this->info('Removed ' . $samples->count() . ' generated responses.');

        return self::SUCCESS;
    }

    /**
     * One answer for one field, obeying that field's own rules.
     *
     * `$row` makes the unique fields unique without having to ask the database
     * what is already taken: a unique value is built from the row number, so
     * two runs of this command never collide either.
     */
    private function valueFor($field, int $row): mixed
    {
        $rules = $field->rules ?? [];
        $options = collect($field->options ?? [])->pluck('value')->filter()->values();

        // An optional field is sometimes left blank, because a table where
        // every cell is filled teaches the owner nothing about the empty ones.
        if (! $field->required && $field->type !== 'image' && $row % 4 === 0) {
            return null;
        }

        return match ($field->type) {
            'letters' => $this->arabicName($row, $field->key),
            'text' => $this->arabicText($row, $field->key),
            'textarea' => 'سجل تجريبي رقم ' . $row . ' - يُحذف بالأمر form:sample --clean.',
            'email' => 'sample' . $row . '@example.test',
            'phone' => '09' . str_pad((string) (31000000 + $row * 137), 8, '0', STR_PAD_LEFT),
            'url' => 'https://example.test/sample/' . $row,

            'digits' => $this->digits((int) ($rules['length'] ?? 11), $row),

            'number' => (string) random_int(
                (int) ($rules['min'] ?? 0),
                max((int) ($rules['min'] ?? 0), (int) ($rules['max'] ?? 10))
            ),

            'date' => $this->date($field->key, $rules),

            'time' => sprintf('%02d:%02d', random_int(7, 20), random_int(0, 59)),
            'datetime' => now()->subDays(random_int(0, 30))->format('Y-m-d\TH:i'),

            'select', 'radio' => $options->isNotEmpty() ? $options[($row - 1) % $options->count()] : null,
            'checkboxes' => $options->isNotEmpty() ? $options->take(max(1, $row % 3))->values()->all() : null,
            'boolean' => $row % 2 === 0,

            'image' => $this->option('with-images') ? $this->picture($row) : null,

            default => 'سجل تجريبي ' . $row,
        };
    }

    /**
     * A date that reads as plausible for what the field is asking.
     *
     * A sample is there to be looked at, and a father born in 2010 makes the
     * whole table look like nonsense - so a field that asks for a birth date
     * gets an adult's, and anything else gets a recent one. An explicit range
     * on the field always wins.
     */
    private function date(string $key, array $rules): string
    {
        $earliest = isset($rules['min_date']) ? Carbon::parse($rules['min_date']) : null;
        $latest = isset($rules['max_date']) ? Carbon::parse($rules['max_date']) : null;

        $isBirthday = str_contains($key, 'birth') || str_contains($key, 'dob');

        $from = $earliest ?? ($isBirthday ? now()->subYears(65) : now()->subYears(2));
        $to = $latest ?? ($isBirthday ? now()->subYears(22) : now());

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        $days = max(1, $from->diffInDays($to));

        return $from->copy()->addDays(random_int(0, (int) $days))->toDateString();
    }

    /** A number of exactly `$length` digits, different for every row. */
    private function digits(int $length, int $row): string
    {
        $tail = str_pad((string) $row, 4, '0', STR_PAD_LEFT);
        $head = '';
        for ($i = 0; $i < max(0, $length - strlen($tail)); $i++) {
            $head .= (string) random_int($i === 0 ? 1 : 0, 9);
        }

        return substr($head . $tail, 0, $length);
    }

    private function arabicName(int $row, string $key): string
    {
        $first = ['أحمد', 'محمد', 'سمير', 'خالد', 'فادي', 'ياسر', 'عمر', 'زياد', 'بشار', 'مروان'];
        $last = ['العلي', 'الحسن', 'الخطيب', 'الشامي', 'الحلبي', 'الديري', 'الحموي', 'السيد', 'النجار', 'القاسم'];

        // The key takes part so the father's name and the mother's name do not
        // come out identical on the same row.
        $shift = crc32($key) % 10;

        return str_contains($key, 'family') || str_contains($key, 'last')
            ? $last[($row + $shift) % 10]
            : $first[($row + $shift) % 10];
    }

    private function arabicText(int $row, string $key): string
    {
        $places = ['مدينة دوما', 'بلدة حرستا', 'بلدية التل', 'مدينة الزبداني', 'بلدة قطنا', 'مدينة داريا'];

        return $places[($row + crc32($key)) % count($places)];
    }

    /**
     * A small picture, generated rather than shipped.
     *
     * Written by hand because the host has no image library: a solid colour
     * png, a different shade per row, so the thumbnails in the table and the
     * pictures in the export are visibly distinct.
     */
    private function picture(int $row): UploadedFile
    {
        $width = 96;
        $height = 96;

        $shades = [[220, 38, 38], [37, 99, 235], [22, 163, 74], [202, 138, 4], [147, 51, 234]];
        [$r, $g, $b] = $shades[$row % count($shades)];

        $raw = '';
        for ($y = 0; $y < $height; $y++) {
            $raw .= chr(0);
            for ($x = 0; $x < $width; $x++) {
                // A diagonal band, so it reads as an image rather than a blank.
                $band = (($x + $y + $row * 7) % 48) < 24;
                $raw .= chr($band ? $r : 245) . chr($band ? $g : 245) . chr($band ? $b : 245);
            }
        }

        $chunk = fn (string $type, string $data) => pack('N', strlen($data)) . $type . $data . pack('N', crc32($type . $data));

        $png = "\x89PNG\r\n\x1a\n"
            . $chunk('IHDR', pack('NN', $width, $height) . chr(8) . chr(2) . chr(0) . chr(0) . chr(0))
            . $chunk('IDAT', gzcompress($raw))
            . $chunk('IEND', '');

        $path = tempnam(sys_get_temp_dir(), 'form-sample') . '.png';
        file_put_contents($path, $png);

        return new UploadedFile($path, 'sample-' . $row . '.png', 'image/png', null, true);
    }
}
