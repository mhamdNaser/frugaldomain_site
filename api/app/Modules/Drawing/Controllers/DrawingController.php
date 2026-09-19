<?php

namespace App\Modules\Drawing\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Drawing\Models\Drawing;
use App\Modules\Drawing\Requests\StoreDrawingRequest;
use App\Modules\Drawing\Resources\DrawingResource;
use App\Modules\Drawing\Services\DrawingModerationService;
use App\Modules\Drawing\Services\DrawingUsageRecorder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * A signed-in user's own drawings.
 *
 * Every route here is behind `auth:sanctum`, and every one that touches a
 * specific drawing goes through `ownedOrFail`. Checking ownership in one
 * helper rather than per-action is what stops the next endpoint from being
 * the one that forgets.
 */
class DrawingController extends Controller
{
    public function __construct(
        private DrawingModerationService $moderation,
        private DrawingUsageRecorder $usage,
    ) {
    }

    /** Hard ceiling, so a caller cannot ask for the whole table in one page. */
    private const MAX_PER_PAGE = 60;

    /** The signed-in user's drawings, newest first. */
    public function index(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 20), self::MAX_PER_PAGE);

        $query = Drawing::where('user_id', $request->user()->id);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        if ($search = $request->input('search')) {
            $query->where('title', 'like', '%' . $search . '%');
        }

        $drawings = $query->latest()->paginate($perPage);

        return DrawingResource::collection($drawings);
    }

    public function store(StoreDrawingRequest $request)
    {
        $data = $request->validated();
        $document = $data['document'];

        $drawing = Drawing::create([
            'user_id' => $request->user()->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'document' => $document,
            'width' => (int) round($document['width']),
            'height' => (int) round($document['height']),
            'element_count' => count($document['paths'] ?? []) + count($document['textItems'] ?? []),
            'allow_reuse' => $data['allow_reuse'] ?? true,
            'tags' => $data['tags'] ?? null,
            'status' => Drawing::STATUS_PRIVATE,
        ]);

        if (! empty($data['thumbnail'])) {
            $this->storeThumbnail($drawing, $data['thumbnail']);
        }

        // Asking to publish only ever queues it for review.
        if (! empty($data['submit_for_review'])) {
            $this->moderation->submit($drawing);
            $drawing->refresh();
        }

        return (new DrawingResource($drawing))->withDocument()
            ->response()
            ->setStatusCode(201);
    }

    /** One drawing, with its document, for reopening in the editor. */
    public function show(Request $request, Drawing $drawing)
    {
        $this->ownedOrFail($request, $drawing);

        return (new DrawingResource($drawing->load('user')))->withDocument();
    }

    public function update(StoreDrawingRequest $request, Drawing $drawing)
    {
        $this->ownedOrFail($request, $drawing);

        $data = $request->validated();
        $document = $data['document'];

        /*
         * Editing a published drawing sends it back for review.
         *
         * Without this, approval would be a one-time gate: publish something
         * harmless, wait for the tick, then replace its contents with anything
         * at all while it stays live in the gallery.
         */
        $wasPublished = $drawing->status === Drawing::STATUS_PUBLISHED;

        $drawing->update([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'document' => $document,
            'width' => (int) round($document['width']),
            'height' => (int) round($document['height']),
            'element_count' => count($document['paths'] ?? []) + count($document['textItems'] ?? []),
            'allow_reuse' => $data['allow_reuse'] ?? $drawing->allow_reuse,
            'tags' => $data['tags'] ?? $drawing->tags,
        ]);

        if (! empty($data['thumbnail'])) {
            $this->storeThumbnail($drawing, $data['thumbnail']);
        }

        if ($wasPublished) {
            $this->moderation->requeueAfterEdit($drawing);
            $drawing->refresh();
        }

        return (new DrawingResource($drawing))->withDocument();
    }

    public function destroy(Request $request, Drawing $drawing)
    {
        $this->ownedOrFail($request, $drawing);
        $drawing->delete();

        return response()->json(['message' => 'Drawing deleted.']);
    }

    /** Ask an administrator to publish this drawing. */
    public function submit(Request $request, Drawing $drawing)
    {
        $this->ownedOrFail($request, $drawing);

        $result = $this->moderation->submit($drawing);
        if (! $result['ok']) {
            return response()->json([
                'message' => $result['reason'] === 'already-pending'
                    ? 'This drawing is already waiting to be reviewed.'
                    : 'This drawing is already published.',
            ], 422);
        }

        return new DrawingResource($drawing->refresh());
    }

    /** Take a drawing back out of the review queue or the gallery. */
    public function withdraw(Request $request, Drawing $drawing)
    {
        $this->ownedOrFail($request, $drawing);

        $drawing->update([
            'status' => Drawing::STATUS_PRIVATE,
            'submitted_at' => null,
        ]);

        return new DrawingResource($drawing->refresh());
    }

    /**
     * Record that the signed-in user placed a published drawing in their work.
     *
     * Always answers 200: "already counted" is an ordinary outcome, not an
     * error, and the editor should never interrupt someone's work over it.
     */
    public function recordUsage(Request $request, Drawing $drawing)
    {
        $validated = $request->validate([
            'used_in_drawing_id' => ['nullable', 'integer', 'exists:drawings,id'],
        ]);

        $result = $this->usage->record(
            $drawing,
            $request->user()->id,
            $validated['used_in_drawing_id'] ?? null,
            $request->ip()
        );

        return response()->json($result);
    }

    /**
     * Reject the request unless the caller owns the drawing.
     *
     * A 404 rather than a 403: telling a stranger "that exists but is not
     * yours" leaks which ids are real.
     */
    private function ownedOrFail(Request $request, Drawing $drawing): void
    {
        if ($drawing->user_id !== $request->user()->id) {
            abort(404);
        }
    }

    /**
     * Save a data-URI thumbnail produced by the editor's exporter.
     *
     * Only PNG is accepted and the payload is decoded strictly: this is a
     * user-supplied string being written to disk, so anything that is not
     * exactly what we expect is discarded rather than stored.
     */
    private function storeThumbnail(Drawing $drawing, string $dataUri): void
    {
        if (! preg_match('#^data:image/png;base64,([A-Za-z0-9+/=]+)$#', $dataUri, $matches)) {
            return;
        }

        $binary = base64_decode($matches[1], true);
        if ($binary === false || strlen($binary) > 2 * 1024 * 1024) {
            return;
        }

        // Verify it really is a PNG rather than trusting the declared type.
        if (substr($binary, 0, 8) !== "\x89PNG\r\n\x1a\n") {
            return;
        }

        $path = "drawings/{$drawing->id}.png";
        Storage::disk('public')->put($path, $binary);

        $drawing->update(['thumbnail_path' => $path]);
    }
}
