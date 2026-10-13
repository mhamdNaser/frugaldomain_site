<?php

namespace App\Modules\Form\Support;

use App\Modules\Form\Models\Form;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * The pictures people attach to a response.
 *
 * They are kept on the private disk, never under a public url. A photo of an
 * identity document or a family is the most sensitive thing a form collects,
 * and a public path - however long and random - is a link that can be
 * forwarded, logged by a proxy and indexed. Instead the owner's own request
 * fetches them, through a route that checks the form belongs to them.
 *
 * The stored answer is not the file. It is a small record of it:
 *
 *     {"path": "forms/<form>/<submission>/<key>.jpg",
 *      "name": "id-card.jpg", "size": 148213, "mime": "image/jpeg"}
 *
 * so an export can name the file, a table can show its size, and nothing has
 * to guess at what is on disk.
 */
class FormFiles
{
    public const DISK = 'local';

    /** Where one form's answers keep their files. */
    public static function directory(Form $form, string $submissionUuid): string
    {
        return 'forms/' . $form->uuid . '/' . $submissionUuid;
    }

    /**
     * Put the uploaded files on disk and return what to store in their place.
     *
     * The name on disk is the field's own key, never the name the file
     * arrived with: an uploaded name is attacker-controlled text, and it is
     * kept as a label rather than used as a path.
     */
    public static function store(Form $form, string $submissionUuid, array $files): array
    {
        $stored = [];

        foreach ($files as $key => $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $extension = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'jpg');
            $extension = preg_replace('/[^a-z0-9]/', '', $extension) ?: 'jpg';

            $path = self::directory($form, $submissionUuid) . '/' . $key . '.' . $extension;

            Storage::disk(self::DISK)->putFileAs(
                dirname($path),
                $file,
                basename($path)
            );

            $stored[$key] = [
                'path' => $path,
                'name' => Str::limit(preg_replace('/[\x00-\x1F\x7F]/u', '', $file->getClientOriginalName() ?: basename($path)), 120, ''),
                'size' => $file->getSize(),
                'mime' => $file->getMimeType(),
            ];
        }

        return $stored;
    }

    /**
     * Remove files written for a submission that did not survive.
     *
     * Called when the transaction rolls back - a duplicate unique answer, say.
     * A failure here is logged rather than thrown: the person is already being
     * told why their answer was refused, and a stray file is a tidying problem,
     * not something to replace that message with.
     */
    public static function forget(array $stored): void
    {
        foreach ($stored as $file) {
            try {
                Storage::disk(self::DISK)->delete($file['path']);
            } catch (\Throwable $e) {
                Log::warning('Could not remove an orphaned form upload', [
                    'path' => $file['path'] ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /** Everything a submission attached, when the submission is deleted. */
    public static function forgetSubmission(Form $form, string $submissionUuid): void
    {
        try {
            Storage::disk(self::DISK)->deleteDirectory(self::directory($form, $submissionUuid));
        } catch (\Throwable $e) {
            Log::warning('Could not remove a submission folder', [
                'form' => $form->id,
                'submission' => $submissionUuid,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Is this answer a stored file?
     *
     * Used wherever an answer has to be treated as a file rather than text -
     * the table, the export, the CSV.
     */
    public static function isFile(mixed $value): bool
    {
        return is_array($value) && isset($value['path'], $value['name']);
    }

    /**
     * Guard a path that came back from stored data.
     *
     * The path is written by this class, but it is read back out of a JSON
     * column, and anything read back is checked before it is used to open a
     * file. A path must sit under the form's own folder and may not climb out
     * of it.
     */
    public static function belongsTo(Form $form, string $path): bool
    {
        $prefix = 'forms/' . $form->uuid . '/';

        return str_starts_with($path, $prefix) && ! str_contains($path, '..');
    }
}
