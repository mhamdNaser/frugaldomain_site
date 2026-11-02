<?php

namespace App\Modules\Handwriting\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class HandwritingController extends Controller
{
    private const WAKE_TIMEOUT = 90;
    private const RECOGNITION_TIMEOUT = 240;

    public function recognize(Request $request): JsonResponse
    {
        if (! $this->configured()) {
            return response()->json([
                'message' => 'Handwriting recognition is not configured on this site yet.',
            ], 503);
        }

        $maxKb = (int) config('handwriting.max_image_mb', 8) * 1024;
        $request->validate([
            'image' => ['required', 'file', 'mimes:png,jpg,jpeg,webp', 'max:' . $maxKb],
        ]);

        $wakeStatus = $this->wakeService();
        if ($wakeStatus !== true) {
            return response()->json([
                'message' => $wakeStatus === false
                    ? 'The handwriting Space could not be accessed. Check its URL, visibility and read token.'
                    : 'The handwriting model is still starting. Please try again shortly.',
            ], $wakeStatus === false ? 502 : 503);
        }

        $image = $request->file('image');

        try {
            $serviceRequest = Http::connectTimeout(self::WAKE_TIMEOUT)
                ->timeout(self::RECOGNITION_TIMEOUT)
                ->withHeaders([
                    'X-Handwriting-Key' => (string) config('handwriting.key'),
                ]);
            if (config('handwriting.space_token')) {
                $serviceRequest = $serviceRequest->withToken((string) config('handwriting.space_token'));
            }
            $response = $serviceRequest
                ->attach('image', fopen($image->getRealPath(), 'r'), $image->getClientOriginalName())
                ->post($this->base() . '/api/handwriting/recognize');
        } catch (Throwable $exception) {
            Log::warning('The handwriting service could not be reached', [
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'The handwriting model is waking up or busy. Please try again shortly.',
            ], 503);
        }

        if (! $response->successful()) {
            $message = $response->json('detail') ?: 'Handwriting recognition failed.';
            $status = $response->status() === 401 ? 502 : $response->status();

            return response()->json(['message' => $message], $status);
        }

        return response()->json($response->json(), 200, [
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function wakeService(): ?bool
    {
        $deadline = microtime(true) + self::WAKE_TIMEOUT;
        $lastError = null;

        do {
            try {
                $request = Http::connectTimeout(5)->timeout(15);
                if (config('handwriting.space_token')) {
                    $request = $request->withToken((string) config('handwriting.space_token'));
                }
                $response = $request->get($this->base() . '/api/handwriting/health');

                if ($response->successful() && $response->json('status') === 'ok') {
                    return true;
                }
                if (in_array($response->status(), [401, 403, 404], true)) {
                    Log::warning('The handwriting Space rejected access', [
                        'status' => $response->status(),
                    ]);

                    return false;
                }
            } catch (Throwable $exception) {
                $lastError = $exception;
            }

            if (microtime(true) < $deadline) {
                usleep(2_000_000);
            }
        } while (microtime(true) < $deadline);

        Log::warning('The handwriting service did not wake before the timeout', [
            'error' => $lastError?->getMessage(),
        ]);

        return null;
    }

    private function configured(): bool
    {
        return (bool) config('handwriting.url') && (bool) config('handwriting.key');
    }

    private function base(): string
    {
        return rtrim((string) config('handwriting.url'), '/');
    }
}
