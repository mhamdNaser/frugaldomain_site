<?php

namespace App\Modules\Convert\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The site's side of the conversion service.
 *
 * The browser never talks to the converter directly, and the three reasons are
 * worth keeping in view:
 *
 *   - The key would have to be in the page. Anything in a page is public, and
 *     a public key on a service that costs cpu time is a service somebody else
 *     is using.
 *   - The converter's address would be in the page too, and a Space found by a
 *     stranger is a Space being used by a stranger.
 *   - Our own limits apply here: who may convert, how often, and how large. The
 *     service has limits of its own, but they are the last line, not the first.
 *
 * The file is streamed through rather than stored. Nothing of it reaches this
 * server's disk, which matters because people convert identity documents.
 */
class ConvertController extends Controller
{
    /** The service is asleep until something wakes it, and waking takes a while. */
    private const WAKE_TIMEOUT = 90;
    private const CONVERT_TIMEOUT = 240;

    /**
     * What the converter can do, cached so the page does not wake a sleeping
     * Space just to draw a list of formats.
     */
    public function formats()
    {
        if (! $this->configured()) {
            return response()->json(['message' => 'File conversion is not set up on this site yet.'], 503);
        }

        $formats = Cache::remember('convert:formats:v1', 3600, function () {
            try {
                $response = Http::timeout(self::WAKE_TIMEOUT)->get($this->base() . '/formats');

                return $response->successful() ? $response->json() : null;
            } catch (\Throwable $e) {
                Log::warning('Could not read the conversion formats', ['error' => $e->getMessage()]);

                return null;
            }
        });

        if (! $formats) {
            // Not cached as a failure for an hour: a Space that was merely
            // asleep would then look broken for the rest of it.
            Cache::forget('convert:formats:v1');

            return response()->json(['message' => 'The converter could not be reached. Please try again in a moment.'], 503);
        }

        return response()->json($formats);
    }

    public function document(Request $request)
    {
        return $this->send($request, 'document', [
            'target' => ['required', 'string', 'alpha_num', 'max:8'],
        ]);
    }

    public function audio(Request $request)
    {
        return $this->send($request, 'audio', [
            'target' => ['required', 'string', 'alpha_num', 'max:8'],
            'bitrate' => ['nullable', 'integer', 'between:32,320'],
        ]);
    }

    /**
     * Hand one upload to the converter and hand back what it returns.
     *
     * The upload is attached from its temporary path rather than read into a
     * string: a sixty megabyte recording read into memory is sixty megabytes
     * of this server's memory, for every person converting one at that moment.
     */
    private function send(Request $request, string $kind, array $rules): StreamedResponse|\Illuminate\Http\JsonResponse
    {
        if (! $this->configured()) {
            return response()->json(['message' => 'File conversion is not set up on this site yet.'], 503);
        }

        $maxKb = (int) config("convert.max_{$kind}_mb", 30) * 1024;

        $data = $request->validate(array_merge($rules, [
            'file' => ['required', 'file', 'max:' . $maxKb],
        ]));

        $file = $request->file('file');

        try {
            $pending = Http::timeout(self::CONVERT_TIMEOUT)
                ->connectTimeout(self::WAKE_TIMEOUT)
                ->withHeaders(['X-Convert-Key' => (string) config('convert.key')])
                ->attach('file', fopen($file->getRealPath(), 'r'), $file->getClientOriginalName());

            $payload = ['target' => $data['target']];
            if (! empty($data['bitrate'])) {
                $payload['bitrate'] = $data['bitrate'];
            }

            $response = $pending->post($this->base() . '/convert/' . $kind, $payload);
        } catch (\Throwable $e) {
            Log::warning('The conversion service could not be reached', [
                'kind' => $kind,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'The converter is waking up or busy. Please try again in a moment.',
            ], 503);
        }

        if (! $response->successful()) {
            // The service's own refusals are the useful ones - "this file is
            // protected by a password" - so they are passed through rather
            // than replaced with something generic.
            $message = $response->json('detail') ?: 'The file could not be converted.';

            return response()->json(['message' => $message], $response->status() === 401 ? 502 : $response->status());
        }

        $filename = $this->filenameFrom($response->header('Content-Disposition'), $file->getClientOriginalName(), $data['target']);
        $body = $response->body();

        return response()->streamDownload(function () use ($body) {
            echo $body;
        }, $filename, [
            'Content-Type' => $response->header('Content-Type') ?: 'application/octet-stream',
            'Content-Length' => (string) strlen($body),
            // Somebody's document: no shared cache may keep a copy.
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function configured(): bool
    {
        return (bool) config('convert.url') && (bool) config('convert.key');
    }

    private function base(): string
    {
        return rtrim((string) config('convert.url'), '/');
    }

    /** The converter names the file; this is the fallback when it does not. */
    private function filenameFrom(?string $disposition, string $original, string $target): string
    {
        if ($disposition && preg_match("/filename\*?=(?:utf-8'')?\"?([^\";]+)/i", $disposition, $matches)) {
            $name = urldecode(trim($matches[1], '"'));

            if ($name !== '') {
                return basename($name);
            }
        }

        $stem = Str::limit(pathinfo($original, PATHINFO_FILENAME), 80, '');

        return ($stem ?: 'converted') . '.' . $target;
    }
}
