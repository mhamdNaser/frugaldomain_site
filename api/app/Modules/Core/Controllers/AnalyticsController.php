<?php

namespace App\Modules\Core\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Services\AnalyticsRecorder;
use App\Modules\Core\Services\AnalyticsStatisticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function __construct(
        private readonly AnalyticsRecorder $recorder,
        private readonly AnalyticsStatisticsService $statistics
    ) {
    }

    // ----------------------------------------------------------- collection
    // These two are public: anonymous visitors are exactly who we want to count.

    public function trackVisit(Request $request): JsonResponse
    {
        $data = $request->validate([
            'path' => ['required', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'referrer' => ['nullable', 'string', 'max:512'],
            'session_id' => ['nullable', 'string', 'max:64'],
            'duration' => ['nullable', 'integer', 'min:0', 'max:86400'],
            // Present only on the follow-up beacon that reports how long the
            // visitor stayed; it updates the row instead of adding one.
            'visit_id' => ['nullable', 'integer'],
        ]);

        $visit = $this->recorder->recordPageVisit($request, $data);

        // The id lets the client attach a duration to this exact view later.
        // Kept deliberately small: this fires on every navigation.
        return response()->json(['id' => $visit?->id], $visit ? 200 : 204);
    }

    public function trackIconEvent(Request $request): JsonResponse
    {
        $data = $request->validate([
            'icon_id' => ['required', 'integer', 'exists:icons,id'],
            'event_type' => ['nullable', 'string', 'in:click,view,copy,favorite'],
        ]);

        $this->recorder->recordIconEvent($request, (int) $data['icon_id'], $data['event_type'] ?? 'click');

        return response()->json(null, 204);
    }

    // ------------------------------------------------------------- reporting
    // Admin only; wired behind auth in the route file.

    public function overview(Request $request): JsonResponse
    {
        $days = $this->days($request);

        return response()->json([
            'status' => 'success',
            'data' => [
                'overview' => $this->statistics->overview($days),
                'timeline' => $this->statistics->visitsTimeline($days),
                'top_pages' => $this->statistics->topPages($days, 10),
                'audience' => $this->statistics->audience($days),
                // The statistics page shows only the top three; the dedicated
                // icon page asks for the full list.
                'top_icons' => $this->statistics->iconEngagement($days, 3),
            ],
        ]);
    }

    public function icons(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $this->statistics->iconEngagement($this->days($request), (int) $request->input('limit', 100)),
        ]);
    }

    public function logins(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $this->statistics->recentLogins((int) $request->input('limit', 50)),
        ]);
    }

    public function activeUsers(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $this->statistics->activeUsers($this->days($request), (int) $request->input('limit', 50)),
        ]);
    }

    public function pages(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $this->statistics->topPages($this->days($request), (int) $request->input('limit', 50)),
        ]);
    }

    /** Clamp the window so a hand-edited query string cannot scan the whole table. */
    private function days(Request $request): int
    {
        return max(1, min(365, (int) $request->input('days', 30)));
    }
}
