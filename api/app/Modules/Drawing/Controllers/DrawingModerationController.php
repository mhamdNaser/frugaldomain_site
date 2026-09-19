<?php

namespace App\Modules\Drawing\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Drawing\Models\Drawing;
use App\Modules\Drawing\Resources\DrawingResource;
use App\Modules\Drawing\Services\DrawingModerationService;
use Illuminate\Http\Request;

/**
 * The administrator's review queue.
 *
 * Every route is behind `auth:sanctum` + `role:admin` at the route level, so
 * nothing in here re-checks the role; the routes file is the single place
 * that decides who may moderate.
 */
class DrawingModerationController extends Controller
{
    public function __construct(private DrawingModerationService $moderation)
    {
    }

    /** Everything waiting for a verdict, oldest first so nothing starves. */
    public function queue(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 20), 60);

        $drawings = Drawing::awaitingReview()
            ->with('user')
            ->orderBy('submitted_at')
            ->paginate($perPage);

        return DrawingResource::collection($drawings);
    }

    /**
     * The full drawing, document included, so the reviewer can actually look
     * at what they are approving rather than judging it by its thumbnail.
     */
    public function show(Drawing $drawing)
    {
        return (new DrawingResource($drawing->load('user')))->withDocument();
    }

    public function approve(Request $request, Drawing $drawing)
    {
        $result = $this->moderation->approve($drawing, $request->user()->id);

        if (! $result['ok']) {
            return response()->json(['message' => 'That drawing is already published.'], 422);
        }

        return new DrawingResource($drawing->refresh());
    }

    public function reject(Request $request, Drawing $drawing)
    {
        // A reason is mandatory: a bare rejection tells the author nothing and
        // turns into a support message every time.
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        $this->moderation->reject($drawing, $request->user()->id, $validated['reason']);

        return new DrawingResource($drawing->refresh());
    }

    /** Pull something already published back out of the gallery. */
    public function unpublish(Request $request, Drawing $drawing)
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->moderation->unpublish($drawing, $request->user()->id, $validated['reason'] ?? null);

        return new DrawingResource($drawing->refresh());
    }
}
