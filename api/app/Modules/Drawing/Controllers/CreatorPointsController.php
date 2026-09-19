<?php

namespace App\Modules\Drawing\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Drawing\Models\CreatorPointEntry;
use App\Modules\Drawing\Models\Drawing;
use Illuminate\Http\Request;

/**
 * What a creator has earned.
 *
 * The balance is summed from the ledger on request rather than read from a
 * cached column: these numbers are meant to become money, and a total that
 * can drift from the entries behind it is a total nobody can defend.
 */
class CreatorPointsController extends Controller
{
    public function summary(Request $request)
    {
        $userId = $request->user()->id;

        return response()->json([
            'balance' => CreatorPointEntry::balanceFor($userId),
            'published_drawings' => Drawing::where('user_id', $userId)
                ->where('status', Drawing::STATUS_PUBLISHED)
                ->count(),
            'pending_drawings' => Drawing::where('user_id', $userId)
                ->where('status', Drawing::STATUS_PENDING)
                ->count(),
            // How many distinct people have used this creator's work.
            'total_uses' => (int) Drawing::where('user_id', $userId)->sum('use_count'),
        ]);
    }

    /** The entries behind the balance, so a creator can audit their own total. */
    public function entries(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 30), 100);

        $entries = CreatorPointEntry::where('user_id', $request->user()->id)
            ->with('drawing:id,title,slug')
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'data' => $entries->getCollection()->map(fn ($entry) => [
                'id' => $entry->id,
                'amount' => $entry->amount,
                'reason' => $entry->reason,
                'note' => $entry->note,
                'drawing' => $entry->drawing ? [
                    'id' => $entry->drawing->id,
                    'title' => $entry->drawing->title,
                    'slug' => $entry->drawing->slug,
                ] : null,
                'created_at' => $entry->created_at->toIso8601String(),
            ]),
            'meta' => [
                'current_page' => $entries->currentPage(),
                'last_page' => $entries->lastPage(),
                'total' => $entries->total(),
            ],
        ]);
    }
}
